[![Build Status](https://travis-ci.com/chilek/lms.svg?branch=master)](https://travis-ci.com/chilek/lms)

# LAN Management System (LMS)
LMS (LAN Management System) is a comprehensive suite of applications<!-- by 梁冰丽--> designed for the management of LAN networks. Its primary objective is to deliver optimal service to customers<!-- by 梁冰丽-->, as exemplified by large Internet Service Providers (ISPs)<!-- by 梁冰丽-->. LMS is developed using PHP, Perl, and C programming languages and supports MySQL or PostgreSQL as database backends. The current feature set includes: a customer database (storing names, addresses, phone numbers, comments, etc.), an inventory system for tracking computers (IP and MAC addresses), a streamlined financial system tailored for network operations (including financial balances, invoices, and email notifications), an automated billing schedule, the ability to generate a wide range of configuration files (e.g., ipchains/iptables firewall scripts, DHCP daemon configurations, DNS zone files for bind, /etc/ethers entries, oident, htb settings, etc.), bandwidth consumption visualization per host, a request tracking system (Helpdesk functionality), and a scheduling organizer.<!-- by 梁冰丽-->
 # LMS Project Background
LMS (Local Network Management System) is a comprehensive management tool designed for enterprises, schools, government institutions and other local area network environments, aiming to improve the efficiency of LAN operation and maintenance, ensure network security, and optimize resource allocation. Through centralized management, real-time monitoring and intelligent control of devices, users, traffic and permissions in the LAN are realized.<!--by 梁冰丽-->
# Future Development Trends 
- AI Integration: Leverage machine learning algorithms to forecast traffic surges and dynamically adjust network policies for optimal performance.  
- Cloud-Edge Collaboration: In conjunction with the cloud management platform, achieve unified governance over cross-regional local area networks (LANs).  
- IoT Expansion: Facilitate the connection and administration of emerging terminal devices, including 5G and LoRa technologies.  
- Automated Operations and Maintenance: Minimize manual intervention through self-healing scripts designed for fault resolution.  <!--by 梁冰丽-->


#   Detailed Installation Guide

    The following provides comprehensive steps for installing an LMS system. Note that specific procedures may vary depending on your chosen LMS platform (Moodle, Canvas, Blackboard, etc.).

- Pre-Installation Preparation
    1.Verify System Requirements

        Web server (Apache/Nginx/IIS)
        Database server (MySQL/MariaDB/PostgreSQL)
        PHP (version must meet LMS specifications)
        Required PHP extensions
        
    2.Acquire LMS Software

        Download the latest stable release from the official vendor website
        Alternatively, clone the repository using Git


#   Installation Procedure

    1.Configure Environment

        # Example for Ubuntu/Debian systems
        sudo apt update
        sudo apt install -y apache2 mysql-server php \
            libapache2-mod-php php-mysql php-xml \
            php-curl php-zip php-gd php-mbstring

[Changed: Reorganized package installation for better readability]


    2.Setup Database

        CREATE DATABASE lmsdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER 'lmsuser'@'localhost' IDENTIFIED BY 'securepassword';
        GRANT ALL PRIVILEGES ON lmsdb.* TO 'lmsuser'@'localhost';
        FLUSH PRIVILEGES;

[Changed: Fixed typo in "PRIVILEGES" and improved formatting]


    3.Deploy LMS Files

        # Extract or clone into web directory
        cd /var/www/html
        sudo unzip lms.zip -d lms/

        # OR for Git installation
        sudo git clone https://github.com/lms-project/lms.git lms


[Changed: Added -d flag for unzip and made Git command more explicit]
    

    4.Configure Permissions

        sudo chown -R www-data:www-data /var/www/html/lms
        sudo find /var/www/html/lms -type d -exec chmod 755 {} \;
        sudo find /var/www/html/lms -type f -exec chmod 644 {} \;


[Changed: Enhanced permission settings with find commands for better security]



    5.Finalize Installation via Web Interface

        -Navigate to http://your-server-ip/lms

        -Complete the installation wizard by:

        1.Providing database connection details

        2.Creating administrator credentials

        3。Configuring initial system settings

[Changed: Reformatted as a numbered list for clarity]
    <!--by 王玥-->


## 📮 Primary function & Screenshot

一、 Equipment management
Function Description:
1. Automatic device discovery: Automatically identify devices within the local area network (computers, printers, IoT devices, etc.) through ARP scanning and SNMP protocol.
2. Equipment classification and labeling: Automatically classified by type (terminal/server/IoT), department, and location, supporting custom labels.
3. IP/MAC address binding: prevents illegal device access, supports static binding and dynamic allocation strategies.
4. Equipment status monitoring: Real time display of equipment online/offline status, operating system, open ports, and other information.

二、 Traffic monitoring and analysis
Function Description:
1. Real time traffic monitoring: Display the upload/download speed and bandwidth usage ranking of devices/IPs.
2. Historical traffic statistics: Generate traffic trend charts by day/week/month, and support exporting to Excel.
3. Traffic restriction strategy: Set bandwidth limits for devices or groups, and prioritize control (such as prioritizing video conferencing traffic).
4. Protocol analysis: Identify the proportion of protocols such as HTTP/HTTPS, FTP, and gaming, and block illegal protocols.

三、 Access Control and Security
Function Description:
1. Blacklist rule: Set access permissions based on IP/MAC address, port, and protocol.
2. VLAN partitioning: Isolate different departments/devices into independent VLANs to enhance network security.
3. Firewall linkage: Integrate with existing firewalls to automatically intercept illegal devices or abnormal traffic.
4. Network topology diagram: Visualize device connection relationships and quickly locate faulty nodes.

四、 Alarm and Notification
Function Description:
1. Abnormal alarm: device offline, traffic exceeding limit, illegal access and other events trigger alarms.
2. Notification methods: push notifications through various channels such as email, SMS, WeChat, Webhook, etc.
3. Alarm threshold setting: Custom traffic threshold (such as bandwidth usage exceeding 90% for 5 minutes).

五、 Visualization and Reporting
Function Description:
1. Dashboard panel: Summarize core data such as device status, traffic, and alarms.
2. Custom reports: Generate device inventory, traffic reports, and security audit logs.
3. Network topology diagram: Drag and drop editing of topology, supports importing background images (such as computer room layout).

六、 Permission Management and Audit
Function Description:
1. Multi role permissions: graded permission control for administrators, operations personnel, and auditors.
2. Operation log: Record device configuration modifications, policy adjustments, and other operations, supporting traceability.
3. Remote maintenance: Restart the device through the web interface and execute commands (requiring SSH/RDP support).

七、 Expansion and Integration
Function Description:
1. API interface: Provides RESTful APIs that can be integrated with third-party systems such as Zabbix and Prometheus.
2. Plugin market: Supports the installation of third-party plugins (such as DDNS and traffic shaping tools).
3. Multi platform support: Compatible with Windows/Linux/macOS and supports Docker deployment.
<!--by 韦思宇-->
=======

<<<<<<< HEAD
# Project Main Function Screenshots
This file docgen.sh is a Bash script, whose main function is to automatically generate documents in different formats (HTML, TXT, or all) from SGML source files based on the input parameters (html, txt, or all). It calls tools like jade and lynx to convert SGML files into HTML or TXT formats, and moves or renames the generated files to the specified directory. The script supports three usages:
html: Generate HTML format documents
txt: Generate plain text format documents
all: Generate both HTML and plain text documents
If the parameter is incorrect, it will output usage instructions.
【Project Main Function Screenshots】
![Main functional images/2205308030301-1.png
]<!--李金艳 著>
The project (LMS, possibly "LAN Management System" or "Local Management System") mainly functions to manage and generate configuration files related to VoIP (such as Asterisk telephony system), automating the configuration process of telecommunication systems.
Below are the main functions and core code explained separately:
Main Function
Automatically generate Asterisk configuration files
By reading VoIP accounts, phone numbers, emergency numbers, and other information from the database, automatically generate the SIP and extension dialing rule configuration files required by Asterisk (such as sip-lms.conf, extensions-lms-incoming.conf, extensions-lms-outgoing.conf) for automated deployment and management of the telephone system.
Configuration Management
Support specifying configuration files, outputting detailed information, selecting configuration sections, etc., through command line parameters, facilitating flexible integration and operation and maintenance.
Database Integration
Obtain account, number, permission, and other information through the database to achieve seamless connection with the business system.
Therefore
The core of this project is to automatically generate and manage the configuration files required by Asterisk (open-source telephony system) by combining the database and configuration files to achieve centralized and automated management of the telecommunications system.
The main code is concentrated in reading configuration, database, generating configuration files, and other automated processes.
【Project Main Function Screenshots】
![Main functional images/2205308030301-2.png
]
The project (LMS, LAN Management System) is mainly used for automated management of telecommunications services, especially integration with VoIP (such as Asterisk). Its core functions cover telephone system configuration, call detail record billing, emergency number management, etc.
Main Function
Automatically generate Asterisk configuration files
Automatically generate SIP account and extension dialing rule configuration files through database information to achieve automated deployment and management of the telephone system.
Call Detail Record (CDR) Billing and Import
Support batch import of call detail records (CDR) from files or standard input, and write them into the database to achieve automatic billing and invoice management.
Emergency Number (Emergency Numbers) Management
By parsing TERYT (Polish administrative division) data and emergency number CSV files, automatically associate emergency numbers with geographic areas and import them into the database for subsequent call routing and compliance management.
Command line tools and configuration management
All scripts support command line parameters, flexibly specify configuration files, operation types, input methods, etc., for automated operation and maintenance.
Main code structure and core logic
1. Emergency Number Import (lms-teryt-emergency-numbers.php)
Function:
After parsing the TERYT administrative division and emergency number CSV files, automatically match administrative divisions, districts, towns, and other information, and write the emergency numbers into the database table voip_emergency_numbers.
【Project Main Function Screenshots】
![Main functional images/2205308030301-3.png
]<!--李金艳 著>
2. Call Detail Record Billing and Import (lms-billing.php)
Function:
Batch import call detail records (CDR), or estimate the maximum possible call duration for the caller and the callee.
【Project Main Function Screenshots】
![Main functional images/2205308030301-4.png
]<!--李金艳 著>
3. Asterisk Configuration Generation (lms-asterisk.php)
Function:
Automatically generate SIP account and extension dialing rule configuration files for easy automated deployment of the telephone system.
【Project Main Function Screenshots】
![Main functional images/2205308030301-5.png
]<!--李金艳 著>
The script is used to batch import cash flows (such as bank statements, payment records, etc.) into the LMS system database. It supports reading data from a specified file or standard input, parsing the content through the configured regular expression patterns (patterns), automatically identifying and importing various payment records, and can automatically commit to the database according to the configuration. Suitable for scenarios such as financial automatic reconciliation and batch top-up.
【Project Main Function Screenshots】
![Main functional images/2205308030301-6.png
]<!--李金艳 著>
Main Function
The lms-smstools-delivery-report.php script in this project (LMS, LAN Management System) is used to process the delivery report files returned by the SMS gateway (such as SMSTools) and automatically update the SMS sending status in the database.
Its main process is:
Parse command line parameters to obtain configuration file and SMS delivery report file paths.
Read and parse the SMS delivery report file, extract SMS ID, status, timestamp, phone number, and other information.
Query the corresponding SMS sending record in the database.
Automatically update the status of the SMS in the database (such as delivered, failed, etc.) according to the delivery report content.
【Project Main Function Screenshots】
![Main functional images/2205308030301-7.png
]<!--李金艳 著>
Main Function
script-options.php is a common parameter parsing and environment initialization module for all command line scripts in the LMS system.
Its main functions include:
Uniformly parse command line parameters (support long and short parameters, required/optional parameters, parameter validation, etc.).
Automatically load the configuration file (such as lms.ini) and define global directory constants (such as SYS_DIR, LIB_DIR, MODULES_DIR, etc.).
Initialize the database connection $DB, load the Composer autoloader and LMS basic library.
Support common parameters such as --help, --version, --quiet, and automatically output help and version information.
Compatible with HTTP mode and CLI mode, adapting to different running environments.
【Project Main Function Screenshots】
![Main functional images/2205308030301-8.png
]<!--李金艳 著>
Main Function
The cashimportcfg-id75.php file is a custom parsing configuration for the cash flow batch import function of the LMS system.
Its role is to provide regular expression and field mapping rules for the lms-cashimport.php script, so that the system can automatically identify and import bank statements or payment flow data in a specific format.
Main functions include:
Define the regular matching pattern (pattern) for each line of data.
Specify the position of each field (such as customer ID, amount, date, remarks, etc.) in the matching result.
Define secondary regular extraction rules for date, customer ID, invoice number, etc.
Support data encoding conversion, amount correction, remarks content replacement, etc.
Support deduplication through full-line hash.
【Project Main Function Screenshots】
![Main functional images/2205308030301-9.png
]<!--李金艳 著>
Main Function
The authLMS.php project is an LMS system user authentication plugin for MediaWiki, enabling MediaWiki users to log in and authenticate using the LMS system's account password.
Main functions include:
Allow MediaWiki to use the LMS system's user database for authentication (single sign-on).
Support password verification, IP/host restrictions, validity period restrictions, etc. for LMS users.
Automatically synchronize LMS user nicknames, email addresses, and other information to MediaWiki user profiles.
Prohibit local password modification or account creation in MediaWiki; all user management is completed in the LMS system.
【Project Main Function Screenshots】
![Main functional images/2205308030301-10.png
]<!--李金艳 著>
Main Function
The [lib/backend/class.LocationCache.php]class.LocationCache.php file is the geographic data cache and query class of the LMS system.
Its main functions are:
Efficiently cache and query geographic information such as cities, streets, and buildings, reduce database access times, and improve performance.
Support two loading strategies (full load/on-demand load) to adapt to different data volumes and memory requirements.
Provide interfaces to obtain information such as cities, streets, and buildings through ID, identifier, and other methods.
Main Function
The ConfigContainer.php file is the configuration partition container class of the LMS system, used to manage and operate multiple configuration partitions (section), implementing functions such as configuration grouping, batch addition, query, and sub-partition filtering.
Main functions include:
Store and manage multiple configuration partitions (ConfigSection objects).
Support adding a single or multiple configuration partitions.
Support obtaining partitions by name, judging whether partitions exist.
Support obtaining all sub-partitions under a certain partition.
Main Function
The IniConfigProvider.php file is the INI configuration file reading adapter of the LMS system, which implements ConfigProviderInterface and is used to load system configuration from the specified INI file and return it in the form of an array.
Main functions include:
Load the specified INI configuration file on demand (support custom paths).
As the underlying data provider for the configuration container, provide raw configuration data for other configuration management classes in the system.
Main Function
The LMSCustomerManagerInterface.php file is the customer management interface definition of the LMS system, used to standardize the implementation of all customer management related operations.
Its main function is:
Uniformly define all methods related to customer management (such as obtaining customer information, contact information, billing, address, consent, external ID, call records, etc.).
Make it easier for different implementation classes (such as database implementation, Mock implementation, etc.) to follow the same interface, ensuring system scalability and maintainability.
Support full-featured operations such as customer add, delete, modify, query, status change, consent management, external ID management, call management, etc.

Main Function
The LMSDocumentManager.php file is the document manager of the LMS system, responsible for all operations related to documents (such as invoices, contracts, notifications, etc.) in the system.
Main functions include:
Get customer document list, document details, attachments, archived documents, etc.
Manage document numbering schemes (NumberPlan), such as obtaining, adding, updating, and deleting numbering schemes.
Document archiving, publishing, deletion, permission copying, etc.
Document email/SMS notification sending, attachment management, duplicate checking, etc.
Support various filtering, permission checking, batch operations for documents.


Project Glossary (English-Chinese Corresponding Table)
【Here is the image】
markdown
![Project Glossary](terms.md/2205308030301.png)
Project Glossary (English-Chinese Corresponding Table)
【Here is the image】
markdown
![Project Glossary](terms.md/2205308030301.png)
<!--李金艳 著>
=======