"""
processor framework for json files with some examples
"""
import json
import copy
with open("/tmp/j-displays.json-2019-creator") as f:
	inline = f.read()
	spec=json.loads(inline)
	n = 1
	for d in copy.deepcopy(spec):
		# Sequentialise the ids
		if d[0:2] == "id":
			newid = "id"+str(n)
			if newid != d:
				print(d, newid)
				spec[newid] = spec.pop(d) # change key to newid
			n += 1

		# ~ # Change creator of display
		# ~ print (d, spec[d]['hd'])
		# ~ usr=input("New author? (blank to leave as "+spec[d]['hd'][1]+") ")
		# ~ if usr != '':
			# ~ spec[d]['hd'][1]=usr
			# ~ spec[d]['hd'][2]='59671a1a' # password hash for 'lights' password

		# ~ # Ask whether to keep the current displays	
		# ~ print (d, spec[d]['hd'])
		# ~ usr=input("Keep? Y/n ")
		# ~ if usr == 'n':
			# ~ del spec[d]

		# ~ # Translate for new format of json file
		# ~ for s in spec[d]:
			# ~ if s=='fl':
				# ~ print(d, s, spec[d]['fl'])
				# ~ v = spec[d][s]
				# ~ if v[0]=='1': # fixed colours
					# ~ v[0:9]=['1',v[2],v[5],'1',v[1],v[5],'1',v[1],v[5]]
				# ~ else:
					# ~ if v[0]=='11': #slow change
						# ~ v[0]=2
					# ~ elif v[0]=='15': #med change
						# ~ v[0]=3
					# ~ else: #fast change
						# ~ v[0]=4					
					# ~ v[0:9]=[v[0],v[2],v[5]]*3
				# ~ print (v)
# ~ print(spec)
with open("/tmp/j-displays.json-2019-seqenced", "w") as f:
	f.write(json.dumps(spec, separators=(',', ':')))
