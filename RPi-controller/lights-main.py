import time
# comment next line if animator is installed on the path
import sys, os; sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/../../ws2812-animator')
from animator import anim_init,anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_render, anim_set_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import GradientDesc, gradient_preset, STEP, SMOOTH
import numpy
from colours import *
import urllib.request
import json

def hue_256(pos):
    """Generate rainbow colors across 0-255 positions."""
    if pos < 85:
        return Color(pos * 3, 255 - pos * 3, 0)
    elif pos < 170:
        pos -= 85
        return Color(255 - pos * 3, 0, pos * 3)
    else:
        pos -= 170
        return Color(0, pos * 3, 255 - pos * 3)
    
if __name__ == '__main__':
	anim_init()
	
	print ('Press Ctrl-C to quit.')
	cur_id = "" # Current display ID (to spot changes)
	try:
		while True:
			download = urllib.request.urlopen('http://localhost/web-server/de-q.php')
			print(download)
			data = download.read() # a `bytes` object
			text = data.decode('utf-8') # a `str` object
			spec = json.loads(text)
			if spec['id'] != cur_id: # Need to read the parameters for the new display
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
				anim_define_pattern(gra_desc, segments=int(spec['se'][0]), seg_reverse=int(spec['se'][1]), motion=int(spec['se'][2]), repeat_s=float(spec['se'][3]), reverse=int(spec['se'][4]))
				anim_define_spot(s_size=int(spec['st'][0]), s_colour=int(spec['st'][1][1:],16), s_motion=int(spec['st'][2]), s_secs=float(spec['st'][3]), s_reverse=int(spec['st'][4]))
				anim_define_fade(f_secs=float(spec['fa'][0]), f_blend=int(spec['fa'][1]), f_min=int(spec['fa'][2]), f_max=255)
				anim_set_brightness(255)
				anim_define_sparkle(s_per_k=int(spec['sk'][0]), s_duration=0.1)
				cur_id = spec['id']
			anim_render(time.time()+spec['durn']) # run until we need to check back

	except KeyboardInterrupt:
			print("Interrupted")

# TODO
# CHECK the parameters and do something sensible on error
# What happens on a crash? (maybe external)
# Move from absolute numbers in Create selections to descriptors which are interpreted here
#  to make things simpler for the creator (at the expense of absolute control)
