#
# Based on 2018 program 'example.py' by Dave Hocker
#
try:
    from pyudmx import pyudmx
except:
    sys.path.append(os.path.dirname(os.path.realpath(__file__))+'/pyudmx')
    from pyudmx import pyudmx

from time import sleep, time
import threading

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
            _dmx.send_multi_value(1, _dmx_buffer)
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
    _dmx.open()

    # Start a separate thread for the USB data transfers (IO bound)
    if not _dmx_transfer:
        transfer_loop = threading.Thread(target=usb_transfer_loop)
        transfer_loop.start() # Start the loop; stop it by setting _dmx_transfer to False

    dmx_blank()

def dmx_put_value(dmx_addr=1, value=0):
    _dmx_buffer[dmx_addr-1] = (value & 0xFF)
    #~ if unit==0: print('DEBUG:dmx:69 put dmx_addr=', dmx_addr, 'value=', value)

_UNIT_0_OFFS = 0
_FLOOD_CHANS = 7
import colorsys
def dmx_set_flood_colour(unit=0, colour=0x000000, hue=-1, brightness=255, strobe=0):
    if hue == 361: # special case for white
        (r,g,b)=[255]*3
    elif hue > 0: # Use hue not colour
        rgb=colorsys.hsv_to_rgb(hue/360,1,1)
        (r,g,b) = [int(v*255) for v in rgb]
        # ~ print("DEBUG:dmx:70 r,g,b=",(r,g,b))
    elif colour == 0: # blank
        brightness=0; r=0; g=0; b=0
    else:
        b = colour & 0xFF
        g = (colour >> 8) & 0xFF
        r = (colour >> 16) & 0xFF
        
    # Strobe channel values: 0=off, 128=slow, 192=medium, 240=fast (channel 6 must be 0-10, intermediate values no effect)
    s = [0,128,192,240][_limit(strobe, 0, 3)]
    # Order correctly for the particular channel use of the unit
    start_ix = _UNIT_0_OFFS + unit*_FLOOD_CHANS
    _dmx_buffer[start_ix: start_ix+_FLOOD_CHANS] = [brightness & 0xFF, r, g, b, s, 0, 0]
    # ~ print('DEBUG:dmx:72 unit=', unit, 'colour=', colour, "[0:7]=", _dmx_buffer[0:7], " len buf=", len(_dmx_buffer))

def dmx_set_flood_sequence(unit=0, speed=1):
    seq = [[111,32],[61,50],[161,100],[111,128],[61,150],[161,170],[111,192],[61,200],[61,255],[161,255]][_limit(speed,1,10)-1]
    # Order correctly for the particular channel use of the unit
    start_ix = _UNIT_0_OFFS + unit*_FLOOD_CHANS
    _dmx_buffer[start_ix: start_ix+_FLOOD_CHANS] = [0]*5 + seq # first five channels ignored for sequences
    print('DEBUG:dmx:88 unit=', unit, 'speed=', speed, "[0:7]=", _dmx_buffer[0:7], " len buf=", len(_dmx_buffer))

def _limit(val, min, max):
    return min if val < min else max if val > max else val
    
_LASER_OFFS = 14
_LASER_CHANS = 8
def dmx_set_laser_turn(r=128, g=128, b=128, turn=5, strobe=0):
    # Strobe channel values: 0=off, 128=slow, 192=medium, 240=fast, 255=strobe
    print('DEBUG:dmx:99 r,g,b=', r,g,b, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))
    s = [0,128,192,240,255][_limit(strobe, 0, 4)]
    t=[1,64,80,127,0,0,128,170,192,255][_limit(turn, 1, 10)-1]
    _dmx_buffer[_LASER_OFFS: _LASER_OFFS+_LASER_CHANS] = [0xFF, r, g, b, s, 0, t, 0]
    print('DEBUG:dmx:103 r,g,b,t=', r,g,b,t, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))

def dmx_set_laser_auto(seq):
    s=[201,225,175,200,150][_limit(seq, 1, 5)-1]
    _dmx_buffer[_LASER_OFFS: _LASER_OFFS+_LASER_CHANS] = [0, 0, 0, 0, 0, 0, 0, s]
    print('DEBUG:dmx:108 seq=', seq, "[14:22]=", _dmx_buffer[14:22], " len buf=", len(_dmx_buffer))

def dmx_close():
    dmx_blank()
    sleep(1)
    global _dmx_transfer
    _dmx_transfer = False
    sleep(0.1)
    _dmx.close()
    
if __name__ == "__main__":
    dmx_init()
    dmx_set_laser_auto(4)
    dmx_set_flood_sequence(0,10)
    sleep(5)
    pause=0.5; frames = 25;
    _dmx_buffer[0]=255
    for f in range (frames):
        dmx_set_flood_colour(0,f*10) # increasingly bright blue
        dmx_set_laser_turn(f*10,f*10,f*10)
        sleep(pause)
        dmx_set_flood_colour(0,0) # black
        sleep(pause)
    print('Blank')
    dmx_blank()
    dmx_close()
