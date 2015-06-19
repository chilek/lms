DELIMITER ;;
 
CREATE TRIGGER `log_pub_ip_insert` AFTER INSERT ON `nodes` FOR EACH ROW
BEGIN
   INSERT INTO log_ip_change
   (
      ipaddr_pub_new,
      moddate,
      ownerid
   )
   VALUES
   (
      NEW.ipaddr_pub,
      unix_timestamp(),
      NEW.ownerid
   );
END;;
 
CREATE TRIGGER `log_pub_ip_update` BEFORE UPDATE ON `nodes` FOR EACH ROW
BEGIN
IF OLD.ipaddr_pub != NEW.ipaddr_pub
   THEN
      INSERT INTO log_ip_change
      (
         ipaddr_pub,
         ipaddr_pub_new,
         moddate,
         ownerid
      )
      VALUES
      (
         OLD.ipaddr_pub,
         NEW.ipaddr_pub,
         OLD.moddate,
         OLD.ownerid
      );
   END IF;
END;;
 
CREATE TRIGGER `log_pub_ip_delete` BEFORE DELETE ON `nodes` FOR EACH ROW
BEGIN
   INSERT INTO log_ip_change
   (
      ipaddr_pub,
      moddate,
      ownerid       
   )
   VALUES
   (
      OLD.ipaddr_pub,
      unix_timestamp(),
      OLD.ownerid
   );
END;;
 
DELIMITER ;