import logging
import time
# comment next line if animator is installed on the path
import sys, os
script_path = os.path.dirname(os.path.realpath(__file__))
sys.path.append(script_path + '/../../ws2812-animator')
from animator import anim_init, anim_stop, anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_render, anim_set_max_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import GradientDesc, gradient_preset, STEP, SMOOTH
import numpy
from colours import *
import urllib.request
import json
import threading
import copy

try:
    import RPi.GPIO as gpio
except:
    print('lights-main:14 Failed to import RPi.GPIO, using local dummy library')
    import gpio
# ~ SERVER_URL='http://lymingtonchurch.org/lights/q-de-q.php'
# ~ SERVER_URL='http://salisburys.net/test/q-de-q.php'
SERVER_URL='http://192.168.1.10/web-server/q-de-q.php'
# ~ SERVER_URL='http://localhost/web-server/q-de-q.php'
# ~ SERVER_URL='fail'

# Number of LEDs we're driving (3 strips of 150 plus two in the box)
NUM_LEDS = 150*3+2

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
# ["0"=>"None", "1"=>"Slow", "2"=>"Fast"]
trans_dmx_strobe = [0, 100, 250];

_gpio_chans = [17,27,22] # Three GPIO channels for: LEDs, DMX, Meteors
_gpio_LED = 0
_gpio_DMX = 1
_gpio_MET = 2

_get_spec = json.loads('{"id":"OFF","stat":"OFF","durn":2,"brled":"0","brdmx":"0","brmet":"false"}')
_get_next = 0
_get_LAT = 0.2 # expected latency on server connection
_get_run = True # global flag to stop the get_loop

def init_gpio():
    #~ return
    gpio.setmode(gpio.BCM)
    gpio.setup(_gpio_chans, gpio.OUT, initial=False)

def pause(step):
    """How long to pause for this step of the countdown"""
    return step/20 + 0.75 # i.e. from 1.25 seconds, getting faster to 0.75
def do_countdown():
    """
    A ten step countdown. Each step has its own display spec
    """
    gpio.output(_gpio_chans[_gpio_LED], True) # Make sure the mains is on
    gpio.output(_gpio_chans[_gpio_DMX], True) # Switch on DMX as it takes a while to warm up
    anim_init(led_count=NUM_LEDS, max_brightness=int(spec['brled']))
    logging.info('Start countdown sequence')
    # 10 green blocks
    gra_colours = ([RGB_Black]+[RGB_Green]*4)*10
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_render(time.time()+pause(10))
    # 9 Green blocks
    gra_colours = ([RGB_Black]+[RGB_Green]*4)*9+[RGB_Black]*5
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_render(time.time()+pause(9))
    # 8 Cyan blocks
    gra_colours = ([RGB_Black]+[RGB_Cyan]*4)*8+[RGB_Black]*10
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_render(time.time()+pause(8))
    # 7 Blue blocks
    gra_colours = ([RGB_Black]+[RGB_Blue]*4)*7+[RGB_Black]*15
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_render(time.time()+pause(7))
    # 6 Magenta blocks + meteors
    gra_colours = ([RGB_Black]+[RGB_Magenta]*4)*6+[RGB_Black]*20
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    gpio.output(_gpio_chans[_gpio_MET], True)
    anim_render(time.time()+pause(6))
    # 5 Red blocks + meteors
    gra_colours = ([RGB_Black]+[RGB_Red]*4)*5+[RGB_Black]*25
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    gpio.output(_gpio_chans[_gpio_MET], True)
    anim_render(time.time()+pause(5))
    # 4 Orange blocks + meteors + red spot
    gra_colours = ([RGB_Black]+[RGB_Orange]*4)*4+[RGB_Black]*30
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_define_spot(2, RGB_Red, RIGHT, 1.5)
    gpio.output(_gpio_chans[_gpio_MET], True)
    anim_render(time.time()+pause(4))
    # 3 Yellow blocks + meteors + red spot twice
    gra_colours = ([RGB_Black]+[RGB_Yellow]*4)*3+[RGB_Black]*35
    gra_desc = GradientDesc(gra_colours, repeats=1, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=STOP)
    anim_define_spot(2, RGB_Red, RIGHT, 0.75, REPEAT)
    gpio.output(_gpio_chans[_gpio_MET], True)
    anim_render(time.time()+pause(3))
    # 2 white blocks moving fast, no meteors or spot
    gra_colours = ([RGB_Black]*5+[RGB_White]*10)
    gra_desc = GradientDesc(gra_colours, repeats=2, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=3, seg_reverse=REPEAT, motion=RIGHT, repeat_s=1, reverse=REVERSE)
    gpio.output(_gpio_chans[_gpio_MET], False)
    anim_render(time.time()+pause(2))
    # 1 rapid rainbow all together no spot or meteors
    gra_colours = [RGB_Red, RGB_Blue]
    gra_desc = GradientDesc(gra_colours, repeats=2, blend=SMOOTH, bar_on=0)
    anim_define_pattern(gra_desc, segments=0, seg_reverse=REPEAT, motion=RIGHT, repeat_s=1/2, reverse=REVERSE)
    anim_define_sparkle(200)
    anim_render(time.time()+pause(1))
    # 0 rainbow, sparkly, DMX auto
    gra_colours = [RGB_Red, RGB_Yellow, RGB_Green, RGB_Cyan, RGB_Blue, RGB_Magenta]
    gra_desc = GradientDesc(gra_colours, repeats=2, blend=STEP, bar_on=0)
    anim_define_pattern(gra_desc, segments=6, seg_reverse=REPEAT, motion=RIGHT, repeat_s=5, reverse=REVERSE)
    anim_define_sparkle(50)
    anim_define_spot(3, RGB_White, RIGHT, 0.5, REVERSE)
    anim_define_dmx(d_off_auto_indep=1, d_strobe=250)
    gpio.output(_gpio_chans[_gpio_MET], True)
    anim_render(time.time()+5) # A bit of extra time the first time round
def net_get_loop():
    """
    Try to get the next display spec from the server. If that fails, carry on with the current one.
    Keep looping round, waiting for the duration specified in spec['durn']
    """
    global _get_spec, _get_next
    def wait_t(ts, tnow):
        # Work out when to re-get the data. The longer it is since the time stamp the longer we wait
        age = tnow - ts
        return tnow + (1 if age < 5 else 2 if age < 10 else 3 if age < 20 else 4 if age < 30 else 5)
        
    while _get_run: # run until told to stop by main thread setting this to False (only on exception)
        try:
            text = ''
            download = urllib.request.urlopen(SERVER_URL)
            data = download.read() # read into a 'bytes' object
            text = data.decode('utf-8') # convert to a 'str' object
            with threading.Lock():
                _get_spec = json.loads(text)
                next_t = int(_get_spec['next_t'])
                try: # dmx not filled in if the lights are off
                    dmx_ts = int(_get_spec['dmx']['dmx_ts'])
                except KeyError:
                    dmx_ts = 0
                tnow = time.time()
                # ~ logging.info('next_t ='+str(next_t)+', dmx_ts='+str(dmx_ts))
                _get_next = min(next_t if next_t > tnow+1 else wait_t(next_t, tnow), wait_t(dmx_ts, tnow))
            # ~ logging.info('Fetched id='+_get_spec['id']+', tnow='+str(tnow)+', _get_next='+str(_get_next)+', secs to get_next='+str(_get_next - tnow))
            time.sleep(max(0, _get_next - tnow - _get_LAT)) # Start the get a bit early to allow for the latency
        except:
            logging.error('Error reading de-q. text="'+text+'". Trying again in 1 second')
            time.sleep(1) # wait a second then try again

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
    cur_brled = ''; cur_brdmx = ''; cur_brmet = ''; 
    # Set up the animation structures
    anim_init(led_count=NUM_LEDS)

    try:
        get_loop = threading.Thread(target=net_get_loop)
        get_loop.start() # Start the loop, it gets the spec from the server and puts it in _get_spec
        init_gpio()
        while True:
            # Read from the server (pick up value fetched by get_loop)
            with threading.Lock():
                spec = copy.deepcopy(_get_spec) # make a copy as _get_spec can change at any time
                next_t = _get_next # when we need to check back with the server
            if next_t <= time.time(): # not got anything since the last get so just keep going for another second
                logging.info('next_t is '+str(time.time()-next_t)+' seconds in the past')
                next_t = time.time()+1
                
            # Have our spec, now do it
            logging.info('id='+spec['id']+', cur_id='+cur_id+', tsecs='+time.strftime('%S.')+str(time.time()).split('.')[1])
            
            if spec['id'] == 'OFF': # switch everything off
                cur_id = 'OFF';
                gpio.output(_gpio_chans, False) # Power down all the mains supplies
                anim_stop()
                if spec['stat'] == 'REB': # REBOOT RPi
                    os.system('sudo shutdown -r now')
                time.sleep(max(0, next_t - time.time()))
                
            elif spec['id'] == 'COU': # countdown sequence
                if cur_id != 'COU': # don't keep repeating the countdown sequence
                    cur_id = 'COU'
                    do_countdown()
                # Keep going with the final display until something else comes along
                anim_render(next_t)
                
            else: # Normal display
                #logging.info('Display name: '+spec['hd'][0])
                if spec['id'] != cur_id \
                or spec['hd'][6] != cur_vn \
                or spec['brled'] != cur_brled \
                or spec['brdmx'] != cur_brdmx \
                or spec['brmet'] != cur_brmet: # Changed, need to read the parameters for the new display

                    # DMX lights and lasers
                    dmx_mode = int(spec['fl'][0])

                    # Stop the old animation
                    logging.info('NEW DISPLAY, spec: '+str(spec))
                    anim_stop()
                    
                    # Remember for next time
                    cur_id = spec['id']; cur_vn = spec['hd'][6]
                    cur_brled = spec['brled']; cur_brdmx = spec['brdmx']; cur_brmet = spec['brmet']; 

                    # Make sure the mains is on for LEDs and DMX if required
                    gpio.output(_gpio_chans[_gpio_LED], cur_brled != 0)
                    gpio.output(_gpio_chans[_gpio_DMX], cur_brdmx != 0)
                    
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
        gpio.cleanup()
        raise