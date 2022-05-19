import os

ip_lijst = {'pfsense' : '10.0.1.1', 'webserver 1' : '10.0.0.6', 'webserver 2' : '10.0.0.4', 'database 1' : '10.0.1.2', 'database 2' : '10.0.1.3', 'monitor' : '10.0.1.4'}

for naam, ip in ip_lijst.items():
    response = os.popen(f'ping {ip}').read()
    if 'Received = 4' in response:
        print(f'{naam} is online')
    else:
        print(f'{naam} is offline')