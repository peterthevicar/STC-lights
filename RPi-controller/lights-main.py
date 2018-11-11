import time
# comment next line if animator is installed on the path
import sys, os; sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/../../ws2812-animator')
from animator import anim_init, anim_stop, anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_define_dmx, anim_render, anim_set_max_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import GradientDesc, gradient_preset, STEP, SMOOTH
import numpy
from colours import *
import urllib.request
import json
try:
	import RPi.GPIO as gpio
except:
	print('Failed to import RPi.GPIO, using local dummy library')
	import gpio

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
# ["0"=>"None", "1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast"]
trans_dmx_speed = [0, 10, 7, 4, 2];

_gpio_chans = [17,27,22] # Three GPIO channels for: LEDs, DMX, Meteors
_gpio_LED = 0
_gpio_DMX = 1
_gpio_MET = 2
def init_gpio():
	#~ return
	gpio.setmode(gpio.BCM)
	gpio.setup(_gpio_chans, gpio.OUT, initial=False)

if __name__ == '__main__':
	cur_id = "" # Current display ID (to spot changes)
	try:
		init_gpio()
		while True:
			try:
				download = urllib.request.urlopen('http://localhost/web-server/de-q.php')
				data = download.read() # read into a 'bytes' object
				text = data.decode('utf-8') # convert to a 'str' object
			except:
				print("DEBUG:main:31 error reading de-q")
				text = '{"co": ["#ff0000", "#ffff00", "#00ff00", "#00ffff", "#0000ff", "#ff00ff"], "br": "80", "hd": ["Rainbow", "Peter", "90bfbf8c", 1539615910, 0, 0], "id": "id1", "st": ["0", "#062af9", "1", "3", "2"], "durn": 5, "se": ["4", "2", "2", "2", "2"], "gr": ["1", "1", "0"], "fq": "0", "fa": ["0", "1", "3"], "me": ["1"], "fl": ["1", "#000000", "#ffffff", "1", "3"], "sk": ["1", 8.3]}'

			print("DEBUG:main:33 text=",text)
			spec = json.loads(text)
			print("DEBUG:main:36 id=",spec['id'])
			if spec['id'] == 'sid0': # switch everything off and wait
				gpio.output(_gpio_chans, False) # Power down all the mains supplies
				anim_stop()
				time.sleep(spec['durn'])
			else:
				gpio.output(_gpio_chans[_gpio_LED], True) # Make sure the mains is on
				gpio.output(_gpio_chans[_gpio_LED], True) # Switch on DMX as it takes a while to warm up
				led_max_brightness = int(spec['br'])
				if spec['id'] != cur_id or spec['fq'] == '1': # Need to read the parameters for the new display
					anim_init(led_count=150*3, max_brightness=led_max_brightness)
					print ('DEBUG:main:41 spec=', spec)
					print ('DEBUG:main:4 spec["co"]=', spec['co'])
					
					# Gradient spec
					gra_colours = []
					for c in spec['co']:
						gra_colours.append(int(c[1:],16))
					print(gra_colours)
					if spec['gr'][2] == "0": # Off
						bar_on = bar_off = 0
					elif spec['gr'][2] == "1": # Dash
						bar_on = 2; bar_off = 6
					else: # Dot
						bar_on = 6; bar_off = 2
					gra_desc = GradientDesc(gra_colours, repeats=int(spec['gr'][0]), blend=int(spec['gr'][1]), bar_on=bar_on, bar_off=bar_off)
					
					# Main pattern
					anim_define_pattern(gra_desc, segments=int(spec['se'][0]), seg_reverse=int(spec['se'][1]), motion=int(spec['se'][2]), repeat_s=trans_speed[int(spec['se'][3])], reverse=int(spec['se'][4]))
					anim_define_spot(s_size=trans_spot_size[int(spec['st'][0])], s_colour=int(spec['st'][1][1:],16), s_motion=int(spec['st'][2]), s_secs=trans_speed[int(spec['st'][3])], s_reverse=int(spec['st'][4]))
					
					# Fading
					anim_define_fade(f_secs=trans_fade[int(spec['fa'][0])], f_blend=int(spec['fa'][1]), f_min=trans_fade_min[int(spec['fa'][2])], f_max=100)
					
					# Sparkle
					anim_define_sparkle(s_per_k=trans_spark[int(spec['sk'][0])], s_duration=0.1)
					
					# DMX lights
					dmx_mode = int(spec['fl'][0])
					dmx_secs = trans_dmx_speed[int(spec['fl'][4])]
					#~ print('DEBUG:main:92 dmx_secs=',dmx_secs)
					if int(spec['fl'][3]) == 1: dmx_secs *= 2 # Twice as long for fade
					if dmx_mode == 0: # off
						dmx_posv=[]
					elif dmx_mode == 1: # auto
						dmx_posv = [25, 75]
					elif dmx_mode == 2: # independent, same
						dmx_posv = [0, 0]
					elif dmx_mode == 3: # independent, alternate
						dmx_posv = [0, 50]
						dmx_mode = 2
					anim_define_dmx(d_off_auto_indep=dmx_mode, d_posv=dmx_posv, d_secs=dmx_secs, d_gradient_desc=GradientDesc([int(spec['fl'][1][1:],16),int(spec['fl'][2][1:],16)], 1, int(spec['fl'][3]), bar_on=0))
					
					# Meteors - just switch on or off at the mains
					gpio.output(_gpio_chans[_gpio_MET], spec['fl'][0] == '1')

					cur_id = spec['id']
					
				anim_set_max_brightness(int(spec['br'])) # can change via sysctl interface
				anim_render(time.time()+int(spec['durn'])) # run until we need to check back

	except:
		print('ERROR:main:99 Exception')
		raise
	finally:
		anim_stop()
		gpio.cleanup()
		raise
