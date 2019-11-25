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
        except ValueError:
            pass
            print('ERR:dmx:38 ValueError - no DMX device?')
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
def dmx_put_flood_colour(unit=0, colour=0x000000, brightness=255, strobe='off'):
    b = colour & 0xFF
    g = (colour >> 8) & 0xFF
    r = (colour >> 16) & 0xFF
    # Strobe channel values: 0=off, 128=slow, 192=medium, 240=fast (channel 6 must be 0-10)
    s = {'off':0, 'slow':128, 'med':192, 'fast':240}[strobe]
    # Order correctly for the particular channel use of the unit
    start_ix = _UNIT_0_OFFS + unit*_FLOOD_CHANS
    _dmx_buffer[start_ix: start_ix+_FLOOD_CHANS] = [brightness & 0xFF, r, g, b, s, 0, 0]
    print('DEBUG:dmx:72 unit=', unit, 'colour=', colour, "[0:7]=", _dmx_buffer[0:7])

def dmx_close():
    dmx_blank()
    sleep(0.1)
    global _dmx_transfer
    _dmx_transfer = False
    sleep(0.1)
    _dmx.close()
    
if __name__ == "__main__":
    dmx_init()
    print('Flash black/red')
    t_start = time()
    #dmx_put_value(1,255) # brightness
    pause=0.5; frames = 25;
    _dmx_buffer[0]=255
    for f in range (frames):
        #dmx_put_value(2,f*10) # ramp up red
        dmx_put_flood_colour(0,f*10)
        sleep(pause)
        dmx_put_flood_colour(0,0) # black
        sleep(pause)
        # ~ sleep(max(0, frame_end - time()))
    #~ print('DEBUG:dmx:93', _dmx_debug_f/(time()-_dmx_debug_start_t), 'USB FPS')

    print('Blank')
    dmx_blank()
    dmx_close()
