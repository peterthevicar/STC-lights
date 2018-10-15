import time
# comment next line if animator is installed on the path
import sys, os; sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/../../ws2812-animator')
from animator import anim_init,anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_render, anim_set_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import GradientDesc, gradient_preset, STEP, SMOOTH
import numpy
import numpy
from colours import *
import urllib.request
import json

# ["1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]
trans_speed = [0.0, 40.0, 20.0, 5.0, 1.0, 0.5]
# ["0"=>"No spot", "1"=>"Tiny", "2"=>"Small", "3"=>"Medium", "4"=>"Large", "5"=>"Huge"]
trans_spot_size = [0, 1, 2, 4, 10, 16]
# ["0"=>"None", "1"=>"Very slow", "2"=>"Slow", "3"=>"Medium", "4"=>"Fast", "5"=>"Very fast"]
trans_fade = [0.0, 15.0, 8.0, 2.0, 1.0, 0.15];
# ["1"=>"Subtle", "2"=>"Normal", "CREATE3"=>"Maximum"]
trans_fade_min = [100, 80, 50, 0];
# ["0"=>"No sparkle", "1"=>"Just a touch", "2"=>"Normal", "3"=>"Lots", "4"=>"Lots and lots"]
trans_spark = [0, 10, 20, 100, 250];

if __name__ == '__main__':
	cur_id = "" # Current display ID (to spot changes)
	try:
		while True:
			download = urllib.request.urlopen('http://localhost/web-server/de-q.php')
			print(download)
			data = download.read() # a `bytes` object
			text = data.decode('utf-8') # a `str` object
			spec = json.loads(text)
			if spec['id'] != cur_id or spec['fq'] == '1': # Need to read the parameters for the new display
				anim_init()
				print ('DEBUG: spec=', spec)
				print ('DEBUG: spec["co"]=', spec['co'])
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
				anim_define_pattern(gra_desc, segments=int(spec['se'][0]), seg_reverse=int(spec['se'][1]), motion=int(spec['se'][2]), repeat_s=trans_speed[int(spec['se'][3])], reverse=int(spec['se'][4]))
				anim_define_spot(s_size=trans_spot_size[int(spec['st'][0])], s_colour=int(spec['st'][1][1:],16), s_motion=int(spec['st'][2]), s_secs=trans_speed[int(spec['st'][3])], s_reverse=int(spec['st'][4]))
				anim_define_fade(f_secs=trans_fade[int(spec['fa'][0])], f_blend=int(spec['fa'][1]), f_min=trans_fade_min[int(spec['fa'][2])], f_max=100)
				anim_define_sparkle(s_per_k=trans_spark[int(spec['sk'][0])], s_duration=0.1)
				cur_id = spec['id']
			anim_set_brightness(int(spec['br'])) # can change via sysctl interface
			anim_render(time.time()+int(spec['durn'])) # run until we need to check back

	except KeyboardInterrupt:
			print("Interrupted")

# TODO
# CHECK the parameters and do something sensible on error
# What happens on a crash? (maybe external)
# What happens if you can't read from the internet?
