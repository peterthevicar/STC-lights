# STC-Lights
Software for web controlled lights, including WS2812 LED strips and DMX units

The running project has four parts: Web interface, Server, RPi controller, Hardware interface

1. Web interface (mix of php on server and javascript in client):
  This formats a form on the user's device allowing them to choose how the lights will controlled. This can done by choosing someone else's creation or designing their own from scratch.
  
2. Server (php on server):
  When the user asks for a particular lighting display (via a form submission) the request is queued on the server. The user receives a response saying roughly how long their request will be in the queue before beginning. The server software keeps track of how many requests each display receives and so can give a list of 'most popular' or 'most recent' displays.
  
3. RPi controller (python in RPi connected to the lights)
  The controller polls the web server looking for new requests. If there are no new requests it keeps playing the most recently requested display.
  
4. Hardware interface
  The interfaces to control the lights are in a separate github project and use the GPIO outputs from the RPi. The system can cope with WS2812 type addressable LED strips, DMX addressable units and simple on/off devices.
