[![Build Status](https://travis-ci.com/chilek/lms.svg?branch=master)](https://travis-ci.com/chilek/lms)

# Lan Management System (LMS)
LMS (LAN Management System) is a package of applications for managing LAN networks. 
Its main goal is to provide the best service to customers, as seen in large ISP companies. 
LMS is written in PHP, Perl and C and can use MySQL or PostgreSQL as its database backends. 
The following features are provided at the time: customer database (names, addresses, phones, comments, etc),
computers inventory (IP, MAC), simple financial system suited for network operations, financial balances and invoices, email warnings to users, automatic billing schedule, ability to generate (almost) any kind of config file ie. ipchains/iptables firewall scripts, DHCP daemon configuration, zones for bind, /etc/ethers entries, oident, htb and more, visualization of bandwidth consumption per host, request tracker system (Helpdesk), timetable (Organizer).

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

【这里是图片】插入图片的Markdown语法格式如下：

```markdown
![项目界面截图](images/screenshot1.png)
特别说明：请将图片保存在你的仓库中（例如仓库中新建images文件夹把截图放进去，一并push到Github上）
```

```markdown
![项目界面截图](images/后端1.png)
```markdown
![项目界面截图](images/后端2.png)
```markdown
![项目界面截图](images/前端1.png)
```markdown
![项目界面截图](images/前端2.png)
```markdown
![项目界面截图](images/61.png)
```markdown
![项目界面截图](images/62.png)
```markdown
![项目界面截图](images/63.png)
```markdown
![项目界面截图](images/71.png)
```markdown
![项目界面截图](images/72.png)
```markdown
![项目界面截图](images/73.png)
```markdown
![项目界面截图](images/74.png)
```markdown
![项目界面截图](images/75.png)


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

2.Check off completed items

