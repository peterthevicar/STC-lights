from pyudmx import pyudmx
from time import sleep

dev = pyudmx.uDMXDevice()
dev.open()
dev.send_single_value(1, 255)
for v in range(0,255,10):
	sleep(0.5)
	print(v)
	dev.send_single_value(1, v)
sleep(1)
dev.close()