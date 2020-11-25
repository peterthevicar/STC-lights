import logging
import time
# comment next line if animator is installed on the path
import sys, os
script_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append(script_path + '/../../ws2812-animator')
from animator import anim_init, anim_stop, anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_render, anim_set_max_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import GradientDesc, gradient_preset, STEP, SMOOTH
from dmx import dmx_init, dmx_put_value, dmx_set_flood_colour, dmx_set_flood_sequence, dmx_set_laser_turn, dmx_set_laser_auto, dmx_blank, dmx_close
import numpy
from colours import *
import urllib.request
import json
import threading
import copy
"""
net_get_loop polls the website for the latest display spec
This is picked up and interpreted by the main while True loop
The first field in the spec is the id, special values are:
  cou - countdown which calls do_countdown to handle the display directly
  OFF - switch lights off or reboot
Other values of id mean the rest of the data in the spec controls the lights
See d_create.php for full format of the pattern display spec
See the end of q-de-q.php for extra fields added during de-q such as time till next change.
  This includes adding in a whole dmx section made by concatenating the 
  floodlight specs which are set by a separate web interface from the
  main pattern selection. The effect is that a new pattern sets the dmx values
  to a default arrangement which can then be overriden by the other web interface.
  The purpose is to give some immediate control to the user rather than having to wait
  for the current pattern to time out.
  The format for the dmx specs are in the x-*.php files
The web interface uses words which it translates into integers for the spec
These integers are then translated by the trans_xxx globals below into the values
  required by the various light controlling APIs. That means that "very slow" can
  be changed here without affecting anything in the web interfaces.
Once the values are obtained, the APIs are called to get the lights set appropriately
There are two APIs: one for ws2812 lights and the other for DMX lights
"""
try:
	import RPi.GPIO as gpio
except:
	print('lights-main:14 Failed to import RPi.GPIO, using local dummy library')
	import gpio
SERVER_URL='http://lymingtonchurch.org/lights/q-de-q.php'
# ~ SERVER_URL='http://salisburys.net/test/q-de-q.php'
# ~ SERVER_URL='http://192.168.1.10/web-server/q-de-q.php'
# ~ SERVER_URL='http://localhost/web-server/q-de-q.php'
# ~ SERVER_URL='fail'

# Number of LEDs we're driving (plus 2 in the box)
NUM_LEDS = 584+2

# After this many seconds without being changed the laser will blank
_LASER_TIMEOUT = 120

# ["1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]
trans_speed = [1000.0, 40.0, 20.0, 5.0, 1.0, 0.5]

# ["0"=>"No spot", "1"=>"Tiny", "2"=>"Small", "3"=>"Medium", "4"=>"Large", "5"=>"Huge"]
trans_spot_size = [0, 1, 2, 4, 10, 16]

# ["0"=>"None", "1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]
trans_fade = [0.0, 15.0, 8.0, 2.0, 1.0, 0.15];

# ["1"=>"Subtle", "2"=>"Normal", "CREATE3"=>"Maximum"]
trans_fade_min = [100, 80, 50, 0];

# ["0"=>"No sparkle", "1"=>"Just a touch", "2"=>"Normal", "3"=>"Lots", "4"=>"Lots and lots"]
trans_spark = [0, 10, 20, 100, 250];

# 0-4 off, fixed colour, slow auto sequence, medium auto, fast auto
trans_dmx_speed = [0, 0, 1, 5, 10];

# ["0"=>"None", "1"=>"Slow", "2"=>"Fast"]
trans_dmx_strobe = [0, 100, 250];

_gpio_chans = [17,27,22] # Three GPIO channels for: LEDs, DMX, Meteors
_gpio_LED = 0
_gpio_DMX = 1
_gpio_MET = 2

_get_text = '{"id":"OFF","stat":"OFF","durn":2,"brled":"0","brdmx":"0","brmet":"false"}'
_get_spec = json.loads(_get_text)
_get_next = 0
_get_LAT = 0.2 # expected latency on server connection
_get_run = True # global flag to stop the get_loop

def init_gpio():
	#~ return
	gpio.setmode(gpio.BCM)
	gpio.setup(_gpio_chans, gpio.OUT, initial=False)

def net_get_loop():
	"""
	Try to get the next display spec from the server. If that fails, carry on with the current one.
	Keep looping round, waiting for the duration specified in spec['durn']
	"""
	global _get_spec, _get_next, _get_text
	def wait_t(ts, tnow):
		# Work out when to re-get the data. The longer it is since the time stamp the longer we wait
		age = tnow - ts
		return tnow + (1 if age < 5 else 2 if age < 10 else 3 if age < 20 else 4 if age < 30 else 5)
		
	while _get_run: # run until told to stop by main thread setting this to False (only on exception)
		try:
			text = ''
			response = urllib.request.urlopen(SERVER_URL,timeout=3)
			data = response.read() # read into a 'bytes' object
			text = data.decode('utf-8') # convert to a 'str' object
			if text == '': raise ValueError('no text retruned by de-q')
			tnow = time.time()
			with threading.Lock():
				_get_text = text
				_get_spec = json.loads(text)
				if _get_spec['id']=='OFF':
					_get_next = tnow + (1 if _get_spec['stat']=='STA' else 30)
				else:
					next_t = int(_get_spec['next_t'])
					dmx_ts = max(int(_get_spec['dmx']['Top']['f_ts']),int(_get_spec['dmx']['Clock']['f_ts']),int(_get_spec['dmx']['Window']['f_ts']))
					_get_next = min(next_t if next_t > tnow+1 else wait_t(next_t, tnow), wait_t(dmx_ts, tnow))
			logging.info('Fetched id='+_get_spec['id']+', sleep='+str(round(_get_next - tnow -_get_LAT,2)))
			time.sleep(max(0, _get_next - tnow - _get_LAT)) # Start the get a bit early to allow for the latency
		except Exception as e:
			logging.error('De-q exception:'+str(e)+', text='+text)
			time.sleep(1) # wait a second then try again

def process_dmx_spec(ds, brdmx):
	"""Look in dmx_spec and make calls to dmx library according to the mode in use"""
	# First the floods
	for i in range(3):
		ds1=ds[['Top','Clock','Window'][i]]
		if ds1['f_mode'] == 'off':
			dmx_set_flood_colour(i, 0)
		elif ds1['f_mode'] == 'col':
			dmx_set_flood_colour(i, hue=int(ds1['f_col']), brightness=int(brdmx), strobe=int(ds1['f_strobe']))
		elif ds1['f_mode'] == 'seq':
			dmx_set_flood_sequence(i, int(ds1['f_seq']))
	# ~ # Now the laser
	# ~ if ds['l_mode'] == 'off':
		# ~ dmx_set_laser_turn(0,0,0)
	# ~ elif ds['l_mode'] == 'tur':
		# ~ dmx_set_laser_turn(int(ds['l_R']), int(ds['l_G']), int(ds['l_B']), int(ds['l_spd']), int(ds['l_strobe']))
	# ~ elif ds['l_mode'] == 'seq':
		# ~ dmx_set_laser_auto(int(ds['l_seq']))

def pause(step):
	"""How long to pause for this step of the countdown"""
	return step/30 + 0.7 # i.e. from 1 second, getting faster to 0.7
def do_countdown():
	"""
	A ten step countdown. Each step has its own display spec
	"""
	dmx_blank()
	gpio.output(_gpio_chans[_gpio_LED], True) # Make sure the mains is on
	gpio.output(_gpio_chans[_gpio_DMX], True) # Switch on DMX as it takes a while to warm up
	anim_set_max_brightness(int(spec['brled']))
	logging.info('Start countdown sequence')
	# 10 Red blocks
	gra_colours = ([RGB_Black]+[RGB_Red]*4)*10
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	dmx_set_flood_colour(0, RGB_White, strobe=3)
	dmx_set_flood_colour(1, RGB_White, strobe=3)    
	anim_render(time.time()+pause(10))
	# 9 Yellow blocks
	gra_colours = ([RGB_Black]+[RGB_Yellow]*4)*9+[RGB_Black]*5
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	dmx_set_flood_colour(0, 0)
	dmx_set_flood_colour(1, 0)    
	anim_render(time.time()+pause(9))
	# 8 Green blocks
	gra_colours = ([RGB_Black]+[RGB_Green]*4)*8+[RGB_Black]*10
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	dmx_set_laser_turn(0,255,0,4)
	anim_render(time.time()+pause(8))
	# 7 Cyan blocks
	gra_colours = ([RGB_Black]+[RGB_Cyan]*4)*7+[RGB_Black]*15
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	dmx_set_laser_turn(0,0,255,7)
	anim_render(time.time()+pause(7))
	# 6 Magenta blocks + floods
	gra_colours = ([RGB_Black]+[RGB_Magenta]*4)*6+[RGB_Black]*20
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	dmx_set_flood_colour(0, RGB_Magenta)
	dmx_set_flood_colour(1, RGB_Magenta)
	dmx_set_laser_turn(0,0,255,7)
	anim_render(time.time()+pause(6))
	# 5 Red blocks + floods
	gra_colours = ([RGB_Black]+[RGB_Red]*4)*5+[RGB_Black]*25
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	gpio.output(_gpio_chans[_gpio_MET], True)
	dmx_set_flood_colour(0, RGB_Red)
	dmx_set_flood_colour(1, RGB_Red)
	dmx_set_laser_turn(255,0,0,8)
	anim_render(time.time()+pause(5))
	# 4 Yellow blocks + red spot + floods
	gra_colours = ([RGB_Black]+[RGB_Yellow]*4)*4+[RGB_Black]*30
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	anim_define_spot(2, RGB_Red, RIGHT, 1.5)
	dmx_set_flood_colour(0, RGB_Yellow)
	dmx_set_flood_colour(1, RGB_Yellow)
	dmx_set_laser_turn(255,255,0,8)
	anim_render(time.time()+pause(4))
	# 3 Green blocks + red spot twice + floods
	gra_colours = ([RGB_Black]+[RGB_Green]*4)*3+[RGB_Black]*35
	gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
	anim_define_spot(2, RGB_Red, RIGHT, 0.75, REPEAT)
	dmx_set_flood_colour(0, RGB_Green)
	dmx_set_flood_colour(1, RGB_Green)
	dmx_set_laser_turn(255,255,255,9)
	anim_render(time.time()+pause(3))
	# 2 Cyan blocks moving fast, floods flashing
	gra_colours = ([RGB_Black]*5+[RGB_White]*10)
	gra_desc = GradientDesc(gra_colours, repeats=2, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=RIGHT, repeat_s=1, reverse=REVERSE)
	dmx_set_flood_colour(0, RGB_White, strobe=3)
	dmx_set_flood_colour(1, RGB_White, strobe=3)
	dmx_set_laser_turn(255,255,255,10)
	anim_render(time.time()+pause(2))
	# 1 rapid rainbow all together no spot, floods still flashing
	gra_colours = [RGB_Red, RGB_Blue]
	gra_desc = GradientDesc(gra_colours, repeats=2, blend=SMOOTH, bar_on=0)
	anim_define_pattern(gra_desc, segments=0, seg_reverse=REPEAT, motion=RIGHT, repeat_s=1/2, reverse=REVERSE)
	anim_define_sparkle(200)
	dmx_set_flood_colour(0, RGB_Cyan, strobe=3)
	dmx_set_flood_colour(1, RGB_Magenta, strobe=3)
	dmx_set_laser_turn(0,0,0)
	anim_render(time.time()+pause(1))
	# 0 rainbow, sparkly, floods colour changing
	gra_colours = [RGB_Red, RGB_Yellow, RGB_Green, RGB_Cyan, RGB_Blue, RGB_Magenta]
	gra_desc = GradientDesc(gra_colours, repeats=2, blend=STEP, bar_on=0)
	anim_define_pattern(gra_desc, segments=6, seg_reverse=REPEAT, motion=RIGHT, repeat_s=5, reverse=REVERSE)
	anim_define_sparkle(50)
	anim_define_spot(3, RGB_White, RIGHT, 0.5, REVERSE)
	dmx_set_laser_auto(5)
	# Render this until the time runs out

if __name__ == '__main__':
	logging.basicConfig(
		level=logging.INFO,
		format="%(asctime)s [%(levelname)s] [%(module)s:%(lineno)d:%(funcName)s] %(message)s",
		datefmt='%m/%d/%Y %H:%M:%S',
		handlers=[
			logging.FileHandler(script_path+"/lights.log"),
			logging.StreamHandler()
		])
	# ~ logging.basicConfig(filename='/home/pi/Desktop/RPi-lights/lights.log', format='%(asctime)s %(message)s', level=logging.INFO)
	logging.info('Started logging')

	cur_id = ''; cur_vn = '' # Current display ID and version number (to spot changes)
	cur_brled = ''; cur_brdmx = ''; cur_brmet = ''
	cur_dmx_Top_ts = ''; cur_dmx_Clock_ts = ''; cur_dmx_Window_ts = ''
	last_laser_t = 0 # to implement timeout (set _LASER_TIMEOUT above)
	# Set up the animation structures
	anim_init(led_count=NUM_LEDS)
	dmx_init()

	try:
		get_loop = threading.Thread(target=net_get_loop)
		get_loop.start() # Start the loop, it gets the spec from the server and puts it in _get_spec
		init_gpio()
		while True:
			# Read from the server (pick up value fetched by get_loop)
			with threading.Lock():
				spec = copy.deepcopy(_get_spec) # make a copy as _get_spec can change at any time
				next_t = _get_next # when we need to check back with the server
			logging.info('id='+spec['id']+', cur_id='+cur_id+', wait='+str(round(next_t-time.time(),2)))
			if next_t <= time.time(): # not got anything since the last get so just keep going for another second
				next_t = time.time()+1
				
			# Have our spec, now do it
			#
			# OFF
			#
			if spec['id'] == 'OFF': # switch everything off
				cur_id = 'OFF';
				gpio.output(_gpio_chans, False) # Power down all the mains supplies
				dmx_blank()
				anim_stop()
				if spec['stat'] == 'REB': # REBOOT RPi
					os.system('sudo shutdown -r now')
				else: # OFF or STA - wait 1 or 30 seconds as set in get loop
					time.sleep(max(0, next_t - time.time()))
			#
			# COUNTDOWN
			#    
			elif spec['id'] == 'COU': # countdown sequence
				if cur_id != 'COU': # don't keep repeating the countdown sequence
					cur_id = 'COU'
					do_countdown()
				# Keep going with the final display until something else comes along
				anim_render(next_t) # Time given to countdown is set in sysctl to 25 seconds
			#
			# NORMAL
			#    
			else: # Normal display
				#logging.info('Display name: '+spec['hd'][0])
				
				# Check DMX for changes
				if spec['brdmx'] != cur_brdmx \
					or spec['dmx']['Top']['f_ts'] != cur_dmx_Top_ts \
					or spec['dmx']['Clock']['f_ts'] != cur_dmx_Clock_ts \
					or spec['dmx']['Window']['f_ts'] != cur_dmx_Window_ts:
					# Make sure the mains is on
					gpio.output(_gpio_chans[_gpio_DMX], cur_brdmx != 0)
					# Save for future comparisons
					cur_brdmx = spec['brdmx'];
					cur_dmx_Top_ts = spec['dmx']['Top']['f_ts']; 
					cur_dmx_Clock_ts = spec['dmx']['Clock']['f_ts']; 
					cur_dmx_Window_ts = spec['dmx']['Window']['f_ts']
					# ~ last_laser_t = max(int(cur_dmx_l_ts), last_laser_t)
					# switch off DMX power if brightness is 0
					gpio.output(_gpio_chans[_gpio_DMX], int(cur_brdmx) > 0)
					# Do the magic
					logging.info('DMX change: spec: '+str(spec['dmx']))
					process_dmx_spec(spec['dmx'], cur_brdmx)

				#Check laser_timeout
				# ~ if time.time() - last_laser_t > _LASER_TIMEOUT:
					# ~ dmx_set_laser_turn(0,0,0)
				
				# Check for LED display changes
				if spec['id'] != cur_id \
					or spec['hd'][6] != cur_vn \
					or spec['brled'] != cur_brled:
					# Make sure the mains is on
					gpio.output(_gpio_chans[_gpio_LED], cur_brled != 0)
					# Stop the old animation
					logging.info('NEW DISPLAY, spec: '+str(spec))
					anim_stop()
					# Remember spec for next time
					cur_id = spec['id']; cur_vn = spec['hd'][6]
					cur_brled = spec['brled']; 
					# Put together a new display specification
					anim_set_max_brightness(int(cur_brled))
					# Gradient spec
					gra_colours = []
					for c in spec['co']:
						gra_colours.append(int(c[1:],16))
					# ~ logging.debug(str(gra_colours))
					if spec['gr'][2] == "0": # Off
						bar_on = bar_off = 0
					elif spec['gr'][2] == "1": # Dash
						bar_on = 2; bar_off = 6
					else: # Dot
						bar_on = 6; bar_off = 2
					gra_desc = GradientDesc(gra_colours, repeats=int(spec['gr'][0]), blend=int(spec['gr'][1]), bar_on=bar_on, bar_off=bar_off)
					
					# Main pattern
					anim_define_pattern(gra_desc, segments=int(spec['se'][0]), seg_reverse=int(spec['se'][1]), motion=int(spec['se'][2]), repeat_s=trans_speed[int(spec['se'][3])], reverse=int(spec['se'][4]))
					
					# Spot
					anim_define_spot(s_size=trans_spot_size[int(spec['st'][0])], s_colour=int(spec['st'][1][1:],16), s_motion=int(spec['st'][2]), s_secs=trans_speed[int(spec['st'][3])], s_reverse=int(spec['st'][4]))
					
					# Fading
					anim_define_fade(f_secs=trans_fade[int(spec['fa'][0])], f_blend=int(spec['fa'][1]), f_min=trans_fade_min[int(spec['fa'][2])], f_max=100)
					
					# Sparkle
					anim_define_sparkle(s_per_k=trans_spark[int(spec['sk'][0])], s_duration=0.1)
					
					# Flood lights
					# on/off, colL,colR strobe
					for i in range(3):
						offs = 3*i
						mode = int(spec['fl'][offs])
						if mode == 0: # switch off
							dmx_set_flood_colour(i, 0)
						elif mode == 1: # manual colours
							dmx_set_flood_colour(i, int(spec['fl'][offs+1][1:],16), brightness=int(cur_brdmx), strobe=int(spec['fl'][offs+2]))
						else: # different speeds of colour changing slow, medium, fast
							dmx_set_flood_sequence(i, speed=trans_dmx_speed[mode])
						
					
					# ~ # Lasers
					# ~ # R,G,B speed,strobe
					# ~ dmx_set_laser_turn(int(spec['la'][0]), int(spec['la'][1]), int(spec['la'][2]), \
						# ~ int(spec['la'][3]), int(spec['la'][4]))
					# ~ last_laser_t = time.time()
				   
				anim_render(next_t) # run until we need to check back
				# end of normal display section
			# end of while loop
		# end try
	except:
		logging.exception('Exception handled in lights-main')
		raise
	finally:
		_get_run = False # probably won't make any difference but it feels good!
		anim_stop()
		dmx_close()
		gpio.cleanup()
		raise
