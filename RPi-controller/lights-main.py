import time
# comment next line if animator is installed on the path
import sys, os; sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/../../ws2812-animator')
from animator import anim_init,anim_define_pattern, anim_define_spot, anim_define_fade, anim_define_sparkle, anim_render, anim_set_brightness, RIGHT,LEFT,L2R1,STOP,REPEAT,REVERSE
from gradients import gradient_preset, STEP, SMOOTH
import numpy
from colours import *
import argparse

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
	# Process arguments
	parser = argparse.ArgumentParser()
	parser.add_argument('-c', '--clear', action='store_true', help='clear the display on exit')
	args = parser.parse_args()

	anim_init()
	
	print ('Press Ctrl-C to quit.')
	if not args.clear:
		print('Use "-c" argument to clear LEDs on exit')

	try:
		gra_desc = gradient_preset(5, STEP, 0, 1)
		anim_define_pattern(gra_desc, 1, REVERSE, LEFT, 5, REVERSE)
		anim_define_spot(1, RGB_Magenta, RIGHT, 1)
		anim_define_fade(-1, SMOOTH, 150)
		anim_set_brightness(255)
		anim_define_sparkle(10, 0.1)
		while True:
			anim_render(time.time()+5) # let it run for a bit
			gra_desc = gradient_preset(numpy.random.randint(1,9), STEP, 0, 1)
			anim_define_pattern(gra_desc, 1, REVERSE, LEFT, 5, REVERSE)

	except KeyboardInterrupt:
		if args.clear:
			print("Interrupted")
