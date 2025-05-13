[![构建状态](https://travis-ci.com/chilek/lms.svg?branch=master)](https://travis-ci.com/chilek/lms)

# 局域网管理系统（LMS）
LMS（局域网管理系统）是一套全面的应用程序套件，专为局域网的管理而设计。其主要目标是为客户提供最佳服务，大型互联网服务提供商（ISP）便是其典型代表。LMS 使用 PHP、Perl 和 C 编程语言开发，并支持 MySQL 或 PostgreSQL 作为数据库后端。当前的功能集包括：客户数据库（存储姓名、地址、电话号码、备注等）、用于跟踪计算机的库存系统（IP 和 MAC 地址）、专为网络运营定制的精简财务系统（包括财务余额、发票和电子邮件通知）、自动计费计划、生成各种配置文件的能力（例如，ipchains/iptables 防火墙脚本、DHCP 守护进程配置、bind 的 DNS 区域文件、/etc/ethers 条目、oident、htb 设置等）、按主机显示带宽使用情况、请求跟踪系统（帮助台功能）以及日程安排组织者。<!--梁冰丽 著>
# LMS 项目背景
LMS（本地网络管理系统）是一款专为企业、学校、政府机构及其他局域网环境设计的综合管理工具，旨在提高局域网的运维效率、保障网络安全并优化资源分配。通过集中管理、实时监控以及对局域网内设备、用户、流量和权限的智能控制得以实现。<!--梁冰丽著>

# 未来发展趋势
- 人工智能集成：利用机器学习算法预测流量高峰，并动态调整网络策略以实现最佳性能。
- 云边协同：与云管理平台结合，实现跨区域局域网的统一治理。
- 物联网扩展：支持新兴终端设备的连接与管理，包括 5G 和 LoRa 技术。
- 自动化运维：通过故障解决的自愈脚本减少人工干预。<!--梁冰丽 著>

# 详细安装步骤:
-   以下是安装LMS系统的一般步骤，具体细节可能因您选择的LMS平台(如Moodle、Canvas、Blackboard等)而有所不同。

    通用安装准备
        1.系统要求检查
-       Web服务器(Apache/Nginx/IIS)
-       数据库(MySQL/MariaDB/PostgreSQL)
-       PHP (特定版本，根据LMS要求)
-       必要的PHP扩展

        2.获取LMS软件
-       从官方网站下载最新稳定版
-       或通过Git克隆项目仓库

详细安装步骤
1. 环境配置
    bash
    # 以Linux系统为例
    sudo apt update
    sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-xml php-curl php-zip php-gd php-mbstring

2. 数据库设置
    sql
    CREATE DATABASE lmsdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'lmsuser'@'localhost' IDENTIFIED BY 'securepassword';
    GRANT ALL PRIVILEGES ON lmsdb.* TO 'lmsuser'@'localhost';
    FLUSH PRIVILEGES;

3. 安装LMS核心文件
    bash
    # 解压或克隆到web目录
    cd /var/www/html
    sudo unzip lms.zip
    # 或
    sudo git clone https://github.com/lms-project/lms.git

4. 设置文件权限
    bash
    sudo chown -R www-data:www-data /var/www/html/lms
    sudo chmod -R 755 /var/www/html/lms

5. 通过Web界面完成安装
-       访问 http://yourserver/lms
-       按照安装向导步骤操作
-       提供数据库连接信息
-       设置管理员账户
-       完成初始配置     
<!--王玥 著>

## 📮 项目主要功能说明与截图

一、设备管理
功能说明：
1.自动发现设备：通过ARP扫描、SNMP协议自动识别局域网内设备（电脑、打印机、IoT设备等）。
2.设备分类与标签：按类型（终端/服务器/IoT）、部门、位置自动分类，支持自定义标签。
3.IP/MAC地址绑定：防止非法设备接入，支持静态绑定和动态分配策略。
4.设备状态监控：实时显示设备在线/离线状态、操作系统、开放端口等信息。

二、流量监控与分析
功能说明：
1.实时流量监控：显示设备/IP的上传/下载速率、带宽占用排名。
2.历史流量统计：按日/周/月生成流量趋势图，支持导出Excel。
3.流量限制策略：设置设备或群组的带宽上限，优先级管控（如视频会议流量优先）。
4.协议分析：识别HTTP/HTTPS、FTP、游戏等协议占比，阻断非法协议。

三、访问控制与安全
功能说明：
1.黑白名单规则：基于IP/MAC地址、端口、协议设置访问权限。
2.VLAN划分：将不同部门/设备隔离到独立VLAN，增强网络安全。
3.防火墙联动：与现有防火墙集成，自动拦截非法设备或异常流量。
4.网络拓扑图：可视化展示设备连接关系，快速定位故障节点。

四、告警与通知
功能说明：
1.异常告警：设备离线、流量超限、非法接入等事件触发告警。
2.通知方式：邮件、短信、微信、Webhook等多种渠道推送。
3.告警阈值设置：自定义流量阈值（如带宽占用超过90%持续5分钟）。

五、可视化与报表
功能说明：
1.Dashboard面板：汇总设备状态、流量、告警等核心数据。
2.自定义报表：生成设备清单、流量报告、安全审计日志。
3.网络拓扑图：拖拽式编辑拓扑，支持导入背景图（如机房布局）。

六、权限管理与审计
功能说明：
1.多角色权限：管理员、运维人员、审计员分级权限控制。
2.操作日志：记录设备配置修改、策略调整等操作，支持追溯。
3.远程维护：通过Web界面重启设备、执行命令（需SSH/RDP支持）。

七、扩展与集成
功能说明：
1.API接口：提供RESTful API，可对接第三方系统（如Zabbix、Prometheus）。
2.插件市场：支持安装第三方插件（如DDNS、流量整形工具）。
3.多平台支持：兼容Windows/Linux/macOS，支持Docker部署。
<!--韦思宇 著>