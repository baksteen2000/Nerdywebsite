import os

ip_lijst = {'webserv1' : '10.0.0.2', 'webserv2' : '10.0.0.4', 'dbserv1' : '10.0.1.2', 'dbserv2' : '10.0.1.3', 'monitor' : '10.0.1.4', 'client' : '10.0.2.2', 'pfsense' : '10.0.1.1'}

for naam, ip in ip_lijst.items():
    response = os.popen(f'ping {ip}').read()
    if 'Received = 4' in response:
        print(f'{naam} is online')
    else:
        print(f'{naam} is offline')