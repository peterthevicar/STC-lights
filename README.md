# STC-Lights
Software for publicly controlled lights on the tower of St.Thomas church, Lymington

The running project has four parts: Browser, Server, RPi, Hardware

1. Browser (mix of php on server and javascript in client):
  php file web-server/ui.php
	php read json-history.json (includes response token to prevent flooding)
	(see ui.php comments for format of this file)
	php format this into a selection list
	php creates token andserialize records in token database (using x lock)
	user selects one of the existing displays from the list
	user chooses whether to display it or edit it into a new one
	[user edits the display to create a new one]
	js send back the data as json-request (including token)
2. Server:
  json-request arrives at web-server/proc-enq.php
    check the token
    update token database to invalidate this token (x lock)
    enqueue the request by creating a file with the info and a timestamped name
    return position in queue
  web-server/proc-deq.php checks for new files in the queue. When found:
    read file contents to work out next display
    format json-current with new display data for reading by the RPi
    update json-history to include new display
3. RPi:
  wget json-current.json
  check for changes
  format display data and send to animator
  animator sends required hardware signals
4. Hardware:
  power supply for large amps
  WS2812 strip
  Meteor lights
  USB DMX controller
  DMX RGB spotlights
