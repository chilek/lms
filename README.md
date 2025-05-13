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

# Detailed installation
-    steps:1. Clone the project repository
        First, you need to clone the project repository to your local machine. Open the terminal and run the following command:

    git clone https://github.com/jiugui321/lms.git
    
        
-        2. Enter the project directory

        After cloning, navigate to the project directory:

    cd standard-readme
        
        
 -       3. Install project dependencies

        Use npm to install the required dependencies:
        
    npm install
        
        
 -       4. Verify installation
        
        Once the installation is complete, you can verify if the installation was successful by running the following command:
        
    npm test
    
 -   If all tests pass, it means the project has been installed successfully.                                        
 
    <!--by 王玥-->