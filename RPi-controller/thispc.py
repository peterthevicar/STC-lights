"""
This file has all the variables which change between different setups
(e.g. number of LEDS, DMX channels etc)
The one in the same directory as lights-main gives the default values,
to override put a modified copy into the settings subdirectory where it
will not be overwritten by git
"""
PCID='main'
SERVER_URL='https://salisburys.net/lights-server/q-de-q.php?'+PCID
NUM_LEDS=150
HAVE_DMX_HARDWARE = False
