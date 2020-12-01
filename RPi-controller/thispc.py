"""
This file has all the variables which change between different setups
(e.g. number of LEDS, DMX channels etc)
The one in the same directory as lights-main gives the default values,
to override put a modified copy into the settings subdirectory where it
will not be overwritten by git
"""
PCID='cupola'
SERVER_URL='http://lymingtonchurch.org/lights/q-de-q.php?'+PCID
NUM_LEDS=540+2 # 2 to act as drivers and signal conditioners in the box
HAVE_DMX_HARDWARE = True
