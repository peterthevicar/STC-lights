"""
Runs an asynchronous look submitting the current DMX buffer to the UDMX hardware
This means we can control the frame rate which seems very important
Each unit has a definition which is used to work out what values need to
  be sent to what channels to get the desired effect.
"""
_HAVE_DMX_HARDWARE = True # Set to False if testing with no DMX hardware
try:
    from pyudmx import pyudmx
except:
    sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/pyudmx')
    from pyudmx import pyudmx

from time import sleep, time
import threading
from sys import exit

# DMX globals, including the buffer to be sent continuously
_DMX_UNIVERSE_SIZE = 512
_dmx = None
_dmx_buffer = bytearray([0]*_DMX_UNIVERSE_SIZE)

#~ _dmx_debug_start_t = None
#~ _dmx_debug_f = 0
_dmx_transfer = None
def usb_transfer_loop():
    """
    This is run in a separate thread. It keeps transmitting the DMX buffer
    until asked to close by setting the global _dmx_transfer to False
    """
    # Need to keep resending at the correct refresh rate. QLC+ says it's 30fps
    global _dmx_transfer
    _dmx_transfer = True # Set to false when we want this thread to stop
    print('DEBUG:dmx:28 Begin USB transfer loop')
    #~ global _dmx_debug_start_t, _dmx_debug_f
    #~ _dmx_debug_start_t = time(); _dmx_debug_f = 0
    while _dmx_transfer:
        # With no sleeps we seem to get about 20fps max with full 512 universe
        #~ frame_end = time() + 1/30
        try:
            if _HAVE_DMX_HARDWARE: _dmx.send_multi_value(1, _dmx_buffer)
        except Exception as e:
            print('ERR:dmx:38 Error in DMX send. Check device. e=',e)
            pass
        #~ _dmx_debug_f += 1
        #~ sleep(max(0, frame_end - time()))
    print('DEBUG:dmx:28 End USB transfer loop')

def dmx_blank():
    _dmx_buffer[:] = [0 for v in range(0, _DMX_UNIVERSE_SIZE)]

def dmx_init():
    global _dmx
    print('DEBUG:dmx:48 Starting DMX controller')
    _dmx = pyudmx.uDMXDevice()
    if _HAVE_DMX_HARDWARE: _dmx.open()

    # Start a separate thread for the USB data transfers (IO bound)
    if not _dmx_transfer:
        transfer_loop = threading.Thread(target=usb_transfer_loop)
        transfer_loop.start() # Start the loop; stop it by setting _dmx_transfer to False

    dmx_blank()

def dmx_put_value(dmx_addr=1, value=0):
    _dmx_buffer[dmx_addr-1] = (value & 0xFF)
    #~ if unit==0: print('DEBUG:dmx:69 put dmx_addr=', dmx_addr, 'value=', value)

# ----------------------------------------------------------------------
# Fixture definitions - how are the channels arranged and what values give what results
# ----------------------------------------------------------------------

# My observations about 2018 7ch fixtures:
#  Strobe channel values: 0=off, 128=slow, 192=medium, 240=fast (channel 6 must be 0-10, intermediate values no effect)

# fixture_desc: (0 control_ch, 1 control_normal_operation_val, 2 dim_ch, 3 r_ch, 4 g_ch, 5 b_ch, 6 w_ch, 
#   7 strobe_ch, 8 (strobe_speeds), 
#   9 (sequence_chs), 10 ((sequence_speeds))
_fixture_definition={
  'WP6':(7, 0, 1, 2, 3, 4, 5, 
    6, (0,5,128,220),
    (7,8), ((151,150),(151,200),(151,240),(151,245),(151,255),(201,240),(201,255),(101,240),(101,250),(101,255))
    ),
  'SS54':(6, 0, 1, 2, 3, 4, 0, 
    5, (0,250,254,255),
    (6,7), ((151,150),(151,200),(151,240),(151,255),(201,254),(201,255),(101,220),(101,240),(101,250),(101,255))
    ),
   'UK36':(6, 0, 1, 2, 3, 4, 0,
     5, (0,192,224,240),
     (6,7), ((111,32),(111,50),(111,100),(111,128),(61,150),(61,170),(61,192),(61,200),(61,255),(161,255))
    )
}
# Unit fixtures - DMX start channel and fixture type for each unit
_unit_fixture=((9,'WP6'), (1,'UK36'), (20,'SS54'))


def _limit(val, min, max):
    return min if val < min else max if val > max else val

import colorsys
def dmx_set_flood_colour(unit=0, colour=0x000000, hue=-1, brightness=255, strobe=0):
    w = 0 # only use white LEDs for white colour
    if hue == 361: # special case for white
        (r,g,b)=[255]*3
        w = 255
    elif hue > 0: # Use hue not colour
        rgb=colorsys.hsv_to_rgb(hue/360,1,1)
        (r,g,b) = [int(v*255) for v in rgb]
        # ~ print("DEBUG:dmx:72 hue,r,g,b=",hue,r,g,b)
    elif colour == 0: # blank
        brightness=0; r=0; g=0; b=0
    else:
        b = colour & 0xFF
        g = (colour >> 8) & 0xFF
        r = (colour >> 16) & 0xFF
        
    unit_offs = _unit_fixture[unit][0] - 2 # subtract one for the start channel, one for the used channel
    fd = _fixture_definition[_unit_fixture[unit][1]]
    s = fd[8][_limit(strobe, 0, 3)]
    # Order correctly for the particular channel use of the unit
    _dmx_buffer[unit_offs + fd[0]] = fd[1] # set control to normal operation
    _dmx_buffer[unit_offs + fd[2]] = brightness & 0xFF
    _dmx_buffer[unit_offs + fd[3]] = r
    _dmx_buffer[unit_offs + fd[4]] = g
    _dmx_buffer[unit_offs + fd[5]] = b
    w_ch = fd[6]
    if w_ch > 0: _dmx_buffer[unit_offs + w_ch] = w
    _dmx_buffer[unit_offs + fd[7]] = s
    
   
    # ~ print('DEBUG:dmx:72 unit=', unit, 'colour=', colour, "[0:7]=", _dmx_buffer[0:7], " len buf=", len(_dmx_buffer))

def dmx_set_flood_sequence(unit=0, speed=1):
    unit_offs = _unit_fixture[unit][0] - 2 # subtract one for the start channel, one for the used channel
    fd = _fixture_definition[_unit_fixture[unit][1]]
    # Have to put values in more than one channel, probably sequential but not taking that for granted
    seq_chs=fd[9]
    seq_vals=fd[10][_limit(speed, 1, 10)-1]
    i = 0
    for c in seq_chs:
        _dmx_buffer[unit_offs + c] = seq_vals[i]
        i = i+1
    # ~ print('DEBUG:dmx:92 unit=', unit, 'speed=', speed, "[0:7]=", _dmx_buffer[0:7], " len buf=", len(_dmx_buffer))

   
# ~ _LASER_OFFS = 14
# ~ _LASER_CHANS = 8
# ~ def dmx_set_laser_turn(r=128, g=128, b=128, turn=5, strobe=0):
    # ~ # Strobe channel values: 0=off, 128=slow, 192=medium, 240=fast, 255=strobe
    # ~ print('DEBUG:dmx:101 r,g,b=', r,g,b, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))
    # ~ s = [0,128,192,240,255][_limit(strobe, 0, 4)]
    # ~ s = [0,50,128,175,200][_limit(strobe, 0, 4)]
    # ~ t=[1,64,80,127,0,0,128,170,192,255][_limit(turn, 1, 10)-1]
    # ~ _dmx_buffer[_LASER_OFFS: _LASER_OFFS+_LASER_CHANS] = [0xFF, r, g, b, s, 0, t, 0]
    # ~ print('DEBUG:dmx:105 r,g,b,t=', r,g,b,t, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))

# ~ def dmx_set_laser_auto(seq):
    # ~ s=[201,225,175,200,150][_limit(seq, 1, 5)-1]
    # ~ _dmx_buffer[_LASER_OFFS: _LASER_OFFS+_LASER_CHANS] = [0, 0, 0, 0, 0, 0, 0, s]
    # ~ print('DEBUG:dmx:110 seq=', seq, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))

def dmx_close():
    dmx_blank()
    sleep(1)
    global _dmx_transfer
    _dmx_transfer = False
    sleep(0.1)
    if _HAVE_DMX_HARDWARE: _dmx.close()
    
if __name__ == "__main__":
    dmx_init()
    # ~ dmx_set_laser_auto(4)
    dmx_set_flood_sequence(2,10)
    sleep(3)
    dmx_set_flood_colour(2, 0x800080)
    sleep(3)
    pause=0.5; frames = 25;
    for f in range (frames):
        dmx_set_flood_colour(2,f*10) # increasingly bright blue
        # ~ dmx_set_laser_turn(f*10,f*10,f*10)
        sleep(pause)
        dmx_set_flood_colour(2,0) # black
        sleep(pause)
    print('Blank')
    dmx_blank()
    dmx_close()
