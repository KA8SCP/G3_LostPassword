Installation of D-Star G2/G3 Registration Password Reset (updated 28 April 2019)

    (SSH logon) Run rpm -qa and check if PHPMailer is installed. If not, install latest version that will run under
    currently-installed PHP version.
    Copy to /var/www/html
        resetpw.php
        resetpw2.php
        resetpw3.php
        resetpwmail.php
        resetpw-prepdel.php
        resetpwthx.php
    Copy to /usb/bin
        resetpw-asroot.php
    Config file
        Copy resetpw.ini to /etc
        Edit settings in resetpw.ini for gateway callsign and mail settings
    Create new Postgres table for resetpw in existing database
        SSH logon to gateway as root
        Log onto Postgres dstar database by entering psql -U dstar dstar_global
        List existing tables by entering \dt
        Create password_reset table by entering
        CREATE TABLE password_reset ( id INTEGER PRIMARY KEY DEFAULT
        nextval('password_reset_id_seq'::regclass), email VARCHAR(255), callsign CHAR(8), selector
        CHAR(16), token CHAR (64), password VARCHAR (255), expires BIGINT );
        5. Check by entering \d password_reset
        Exit Postgres by entering \q
    Update Registration logon page
        Edit /opt/products/dstar/D-STAR/WEB-INF/pages/topmenu/topmenu.jsp
        Locate Login button code:
        <html:submit property="nextpage" styleClass="button">
        <bean:message key="button.Login"/>
        </html:submit>
        Add following just below above lines, but put in url of your gateway:



        Forgot your password?
    Update crontab to run resetpw-asroot.php every minute:
        SSH logon to gateway as root
        Edit crontab by entering crontab -e
        Review vi commands, particularly :w and :q. Create new entry to look like:
        *** * * * * php -f /usr/bin/resetpw-asroot.php**

Jim Moen - K6JM – jim@k6jm.com
W6CX D-Star Administrator
Mount Diablo Amateur Radio Club
Concord, CA
