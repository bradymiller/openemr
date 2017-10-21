SET character_set_client = utf8;
DELETE FROM list_options WHERE list_id = 'AUDIT_2nd_Score';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'AUDIT_2nd_Score';
-- MySQL dump 10.14  Distrib 5.5.50-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: openemr
-- ------------------------------------------------------
-- Server version	5.5.50-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `list_options`
--
-- WHERE:  list_id = 'lists' AND option_id = 'AUDIT_2nd_Score' OR list_id = 'AUDIT_2nd_Score'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_2nd_Score','1','Never',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_2nd_Score','2','Less than monthly',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_2nd_Score','3','Monthly',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_2nd_Score','4','Weekly',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_2nd_Score','5','Daily or almost daily',50,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','AUDIT_2nd_Score','AUDIT 2nd Score',309,1,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:46:29
DELETE FROM list_options WHERE list_id = 'AUDIT_Referral';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'AUDIT_Referral';
-- MySQL dump 10.14  Distrib 5.5.50-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: openemr
-- ------------------------------------------------------
-- Server version	5.5.50-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `list_options`
--
-- WHERE:  list_id = 'lists' AND option_id = 'AUDIT_Referral' OR list_id = 'AUDIT_Referral'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_Referral','1','Praise = (Low Risk: 0-7)',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_Referral','2','Brief Intervention = (At Risk: 8-15)',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_Referral','3','Brief Treatment = (Harmful: 16-19)',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_Referral','4','Refer to Specialty Care = (Severe: 20+)',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','AUDIT_Referral','AUDIT Referral',311,1,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:46:29
DELETE FROM list_options WHERE list_id = 'AUDIT_injury';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'AUDIT_injury';
-- MySQL dump 10.14  Distrib 5.5.50-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: openemr
-- ------------------------------------------------------
-- Server version	5.5.50-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `list_options`
--
-- WHERE:  list_id = 'lists' AND option_id = 'AUDIT_injury' OR list_id = 'AUDIT_injury'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_injury','1','No',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_injury','2','',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_injury','3','Yes, but not in the last year',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_injury','4','',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('AUDIT_injury','5','Yes, during the last year',50,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','AUDIT_injury','AUDIT_injury',310,1,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:46:29
DELETE FROM layout_options WHERE form_id = 'LBFAUDIT';
DELETE FROM list_options WHERE list_id = 'lbfnames' AND option_id = 'LBFAUDIT';
-- MySQL dump 10.14  Distrib 5.5.50-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: openemr
-- ------------------------------------------------------
-- Server version	5.5.50-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `list_options`
--
-- WHERE:  list_id = 'lbfnames' AND option_id = 'LBFAUDIT'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lbfnames','LBFAUDIT','AUDIT Secondary Screen',80,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:46:29
-- MySQL dump 10.14  Distrib 5.5.50-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: openemr
-- ------------------------------------------------------
-- Server version	5.5.50-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `layout_options`
--
-- WHERE:  form_id = 'LBFAUDIT'

INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','alcohol_txt','1Remaining AUDIT Questions ALC','',10,31,2,0,255,'',0,0,'','','Remaining Alcohol (AUDIT) Questions (V3)',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','audit_C_total','1Remaining AUDIT Questions ALC','AUDIT-C Universal Screen Total Score',110,2,1,3,5,'',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','audit_eq','1Remaining AUDIT Questions ALC','',100,31,2,0,255,'',1,0,'','','TOTAL Score = AUDIT Universal Screen Score + Score of remaining questions',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','audit_rec','1Remaining AUDIT Questions ALC','Recommendation',140,1,1,0,50,'AUDIT_Referral',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','audit_score_text','1Remaining AUDIT Questions ALC','',80,31,1,0,255,'',1,0,'','','Scoring: 0 – 7 Lower risk, 8 – 15 At risk, 16 – 19 Harmful, 20+ Severe',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','audit_sub_score','1Remaining AUDIT Questions ALC','AUDIT Remaining Questions Score',120,2,1,3,10,'',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank','1Remaining AUDIT Questions ALC','',19,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank0','1Remaining AUDIT Questions ALC','',90,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank1','1Remaining AUDIT Questions ALC','',29,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank10','1Remaining AUDIT Questions ALC','',129,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank11','1Remaining AUDIT Questions ALC','',12,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank12','1Remaining AUDIT Questions ALC','',139,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank2','1Remaining AUDIT Questions ALC','',39,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank3','1Remaining AUDIT Questions ALC','',49,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank4','1Remaining AUDIT Questions ALC','',59,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank5','1Remaining AUDIT Questions ALC','',69,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank6','1Remaining AUDIT Questions ALC','',79,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank7','1Remaining AUDIT Questions ALC','',89,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank8','1Remaining AUDIT Questions ALC','',109,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','blank9','1Remaining AUDIT Questions ALC','',119,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cant_stop','1Remaining AUDIT Questions ALC','1. Times could not stop',17,27,1,100,255,'AUDIT_2nd_Score',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cant_stop_ques','1Remaining AUDIT Questions ALC','',15,31,1,0,100,'',1,0,'','','1. How often during the last year have you found \r\nthat you were not able to stop \r\ndrinking once you had started?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cant_stop_score','1Remaining AUDIT Questions ALC','',18,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cut_down','1Remaining AUDIT Questions ALC','7. Cut down suggested',75,27,1,100,255,'AUDIT_injury',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cut_down_ques','1Remaining AUDIT Questions ALC','',70,31,1,0,100,'',1,0,'','','7. Has a relative or friend, doctor or other health \r\nworker been concerned about your drinking \r\nor suggested that you cut down?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','cut_down_score','1Remaining AUDIT Questions ALC','',78,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','eye_opener','1Remaining AUDIT Questions ALC','3. Eye Opener',35,27,1,100,255,'AUDIT_2nd_Score',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','eye_opener_ques','1Remaining AUDIT Questions ALC','',30,31,1,0,100,'',1,0,'','','3. How often during the last year have you needed\r\n an alcoholic drink in the morning to get yourself \r\ngoing after a heavy drinking session?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','eye_opener_score','1Remaining AUDIT Questions ALC','',38,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','failed_activity','1Remaining AUDIT Questions ALC','2. Failed Activities',25,27,1,100,255,'AUDIT_2nd_Score',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','failed_activity_score','1Remaining AUDIT Questions ALC','',28,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','failed_act_ques','1Remaining AUDIT Questions ALC','',20,31,1,0,100,'',1,0,'','','2. How often during the last year have you failed to\r\n do what was normally expected from you because of\r\n your drinking?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','Guilt_ques','1Remaining AUDIT Questions ALC','',40,31,1,0,100,'',1,0,'','','4. How often during the last year have you had a \r\nfeeling of guilt or remorse after drinking?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','guilt_remorse','1Remaining AUDIT Questions ALC','4. Guilt or Remorse',45,27,1,100,255,'AUDIT_2nd_Score',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','guilt_remorse_score','1Remaining AUDIT Questions ALC','',48,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','injury','1Remaining AUDIT Questions ALC','6. Anyone Injured',65,27,1,100,255,'AUDIT_injury',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','injury_ques','1Remaining AUDIT Questions ALC','',60,31,1,0,100,'',1,0,'','','6. Have you or somebody else been injured as a \r\nresult of your drinking?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','injury_score','1Remaining AUDIT Questions ALC','',68,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','mem_loss','1Remaining AUDIT Questions ALC','5. Memory loss',55,27,1,100,255,'AUDIT_2nd_Score',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','mem_loss_ques','1Remaining AUDIT Questions ALC','',50,31,1,0,100,'',1,0,'','','5. How often during the last year have you been \r\nunable to remember what happened the night \r\nbefore because you had been drinking?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','mem_loss_score','1Remaining AUDIT Questions ALC','',58,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFAUDIT','total_audit','1Remaining AUDIT Questions ALC','TOTAL AUDIT SCORE',130,2,1,3,10,'',1,3,'','','',0,'','F','');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:46:29
