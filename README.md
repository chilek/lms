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

