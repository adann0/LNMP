# LNMP
Linux (Raspbian) - Nginx - MySQL (MariaDB) - PHP

# Quick Setup

    $ sudo passwd root
    $ sudo raspi-config
    $ sudo nano /etc/ssh/sshd_config
    $ sudo adduser <user>
    $ sudo visudo
    
    <user>    ALL=(ALL:ALL) ALL

    $ sudo apt update -y && sudo apt upgrade -y && sudo reboot

    $ sudo deluser -remove-home pi

# Nginx

    $ sudo apt install nginx php-fpm

    $ sudo service nginx
    $ sudo service nginx configtest
    $ sudo service nginx start
    $ sudo service nginx restart
    $ curl -I http://localhost

    $ sudo chown -R www-data:$USER /var/www/
    $ sudo chmod -R 755 /var/www/

    $ sudo nano /etc/php/7.0/fpm/php.ini

    expose_php = Off

    $ sudo mkdir -p /var/www/example.com/html
    $ sudo chown -R www-data:$USER /var/www/example/html
    $ sudo chmod -R 750 /var/www
    $ sudo nano /var/www/example.com/html/index.html

    <html>
    <head><title>Website</title></head>
    <body><h1>Nice</h1></body>
    </html>

    $ sudo nano /etc/nginx/sites-available/example.com

    server {
        listen 80;

        root /var/www/example.com/html
        index index.html index.htm index.php;

        server_name example.com www.example.com;

        location / {
                try_files $uri $uri/ =404;
        }
    }

    $ sudo ln -s /etc/nginx/sites-available/example /etc/nginx/sites-enabled/

    $ sudo nano /etc/nginx/nginx.conf
    
    http {
    . . .
    server_names_hash_bucket_size 64;
    . . .
    server_tokens off;
    . . .
    }
    
    $ sudo nano /etc/hosts

    1.2.3.4 example.com www.example.com
    1.2.3.4 example.fr www.example.fr
    
    $ sudo nginx -t
    $ sudo service nginx restart

# SSL/TLS

    $ sudo mkdir /etc/nginx/ssl/
    $ sudo openssl req -x509 -nodes -sha256 -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/nginx.key -out /etc/nginx/ssl/nginx.crt
    $ sudo openssl dhparam -out /etc/nginx/ssl/dhparam.pem 4096 #cette etape prend du temps

    $ sudo nano /etc/nginx/sites-enabled/default 
    
    server {
    . . .
    server_name _;
    
    ssl_protocols TLSv1.3;# Requires nginx >= 1.13.0 else use TLSv1.2
    ssl_prefer_server_ciphers on; 
    ssl_dhparam /etc/nginx/dhparam.pem; # openssl dhparam -out /etc/nginx/dhparam.pem 4096
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
    ssl_ecdh_curve secp384r1; # Requires nginx >= 1.1.0
    ssl_session_timeout  10m;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off; # Requires nginx >= 1.5.9
    ssl_stapling on; # Requires nginx >= 1.3.7
    ssl_stapling_verify on; # Requires nginx => 1.3.7
    resolver $DNS-IP-1 $DNS-IP-2 valid=300s;
    resolver_timeout 5s; 
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    . . .
    }

    Check : https://cipherli.st/
    
    $ sudo apt-get install python-certbot-nginx

    $ sudo certbot --authenticator standalone --installer nginx -d example.com -d www.example.com --pre-hook "service nginx stop" --post-hook "service nginx start"

    $ sudo nano /etc/nginx/nginx.conf
    
    . . .
    proxy_hide_header X-Powered-By;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Strict-Transport-Security max-age=15768000;
    . . .

# Security

## Wapiti

    $ sudo apt-get install wapiti
    $ wapiti http://example.com -n 10 -b folder
    
## IPTables

    $ sudo apt-get install iptables
    $ chmod +x /etc/init.d/firewall
    $ sudo update-rc.d firewall defaults

## Fail2Ban

    $ sudo apt-get install fail2ban
    $ sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.conf.bak
    $ sudo nano /etc/fail2ban/jail.local
    
    $ sudo mv nginx-*.conf /etc/fail2ban/filter.d
    
    $ sudo service fail2ban restart
    $ sudo fail2ban-client status
    $ sudo fail2ban-client status nginx-http-auth
    $ sudo fail2ban-client set nginx-http-auth unbanip 1.2.3.4

## NMap

(Sur un laptop, pas sur le Serveur)

    $ sudo apt install nmap
    $ mkdir ~/scan_results
    $ mkdir ~/scan_results/syn_scan

    $ sudo tcpdump host 1.2.3.4 -w ~/scan_results/syn_scan/packets

On peut ensuite stopper le processus avec CTRL+Z.

    $ bg

Et on peut lancer la commande fournie par le shell. Dans un autre shell on va lancer cette commande :

    $ sudo nmap -sS -Pn -p- -T4 -vv --reason -oN ~/scan_results/syn_scan/nmap.results 1.2.3.4

    $ fg

On peut stopper avec CTRL+C. Et regarder les résultats avec :

    $ less ~/scan_results/syn_scan/nmap.results

D'autres commandes :

    $ sudo tcpdump -nn -r ~/scan_results/syn_scan/packets 'dst 1.2.3.4' | less
    $ sudo nmap -O -Pn -vv --reason -oN ~/scan_results/versions/os_version.nmap 1.2.3.4


## MySQL (MariaDB)

    $ sudo apt install mysql-server
    $ sudo mysql_secure_installation

    $ sudo mysql -u root

    > CREATE DATABASE <db>;
    > SHOW DATABASES;

    > CREATE USER '<user>'@’%’ IDENTIFIED BY '<password>';
    > GRANT ALL PRIVILEGES ON <db>.* to ‘<user>’@’%’;
    > FLUSH PRIVILEGES;

    > USE <db>;

Un exemple de table pour des utilisateurs, adaptée a recevoir un mot de passe hash par Bcrypt + son IV :

    CREATE TABLE users(
        user_id INT NOT NULL AUTO_INCREMENT,
        user_name VARCHAR(100) NOT NULL,
        user_mail VARCHAR(355) NOT NULL,
        user_password VARCHAR(128) NOT NULL,
        user_iv VARCHAR(80) NOT NULL,
        user_validate BOOLEAN NOT NULL DEFAULT 0,
        PRIMARY KEY ( user_id )
    );

Si on veut faire des INSERT / SELECT a distance (depuis une page web sur un serveur local par exemple), remplacer bind-adress 127.0.0.1 par 0.0.0.0

    $ sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf 

    bind-address            = 0.0.0.0

Dans le fichier config de PHP si on veut utiliser PDO il faut aussi penser a enlever le “;”.

    $ sudo apt-get install php7.0-mysql
    $ sudo phpenmod pdo_mysql
    $ sudo service nginx restart 





