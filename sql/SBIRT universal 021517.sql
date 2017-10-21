SET character_set_client = utf8;
DELETE FROM list_options WHERE list_id = 'drinks_daily';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'drinks_daily';
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
-- WHERE:  list_id = 'lists' AND option_id = 'drinks_daily' OR list_id = 'drinks_daily'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('drinks_daily','1','0-2',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('drinks_daily','2','3-4',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('drinks_daily','3','5-6',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('drinks_daily','4','7-9',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('drinks_daily','5','10+',50,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','drinks_daily','Drinks daily',315,1,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
DELETE FROM list_options WHERE list_id = 'sbirt_freq';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'sbirt_freq';
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
-- WHERE:  list_id = 'lists' AND option_id = 'sbirt_freq' OR list_id = 'sbirt_freq'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','sbirt_freq','SBIRT Frequency',311,1,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_freq','1','Never',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_freq','2','Monthly or less',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_freq','3','2-4 times per month',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_freq','4','2-3 times per week',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_freq','5','4+ times per week',50,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
DELETE FROM list_options WHERE list_id = 'sbirt_gen_freq';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'sbirt_gen_freq';
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
-- WHERE:  list_id = 'lists' AND option_id = 'sbirt_gen_freq' OR list_id = 'sbirt_gen_freq'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','sbirt_gen_freq','SBIRT Gender Frequency',313,1,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_gen_freq','1','Never',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_gen_freq','2','Less than monthly',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_gen_freq','3','Monthly',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_gen_freq','4','Weekly',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_gen_freq','5','Daily or almost daily',50,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
DELETE FROM list_options WHERE list_id = 'sbirt_tob_freq';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'sbirt_tob_freq';
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
-- WHERE:  list_id = 'lists' AND option_id = 'sbirt_tob_freq' OR list_id = 'sbirt_tob_freq'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','sbirt_tob_freq','SBIRT Tobacco Frequency',314,1,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_tob_freq','1','Never',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_tob_freq','2','1-2 times per month',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_tob_freq','3','Weekly',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_tob_freq','4','Almost daily',40,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('sbirt_tob_freq','5','Daily',50,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
DELETE FROM layout_options WHERE form_id = 'LBFSBIRTScreen';
DELETE FROM list_options WHERE list_id = 'lbfnames' AND option_id = 'LBFSBIRTScreen';
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
-- WHERE:  list_id = 'lbfnames' AND option_id = 'LBFSBIRTScreen'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lbfnames','LBFSBIRTScreen','SBIRT Universal Screen',110,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
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
-- WHERE:  form_id = 'LBFSBIRTScreen'

INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','alc_rec','2SBIRT Alcohol','Alcohol Positive/Alert Provider',70,1,1,0,5,'yesno',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','alc_sum','2SBIRT Alcohol','Alcohol Summed Score',60,2,1,3,5,'',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','alc_yr','2SBIRT Alcohol','1. Alcohol yearly frequency',15,27,1,100,255,'sbirt_freq',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','alc_yr_ques','2SBIRT Alcohol','',10,31,1,0,100,'',1,0,'','','1. In the last year, how often do you have a \r\ndrink  containing alcohol?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','alc_yr_score','2SBIRT Alcohol','',18,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank','2SBIRT Alcohol','',19,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank0','2SBIRT Alcohol','',65,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank1','2SBIRT Alcohol','',29,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank11','2SBIRT Alcohol','',5,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank12','4SBIRT Tobacco','',29,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank13','3SBIRT Drugs','',80,31,1,0,255,'',1,0,'','','',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:4:\"Male\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank14','3SBIRT Drugs','',75,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank15','4SBIRT Tobacco','',40,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank2','3SBIRT Drugs','',19,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank3','3SBIRT Drugs','',29,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank4','3SBIRT Drugs','',18,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank5','4SBIRT Tobacco','',19,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank6','1Patient Info','',20,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank7','2SBIRT Alcohol','',39,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank8','3SBIRT Drugs','',9,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','blank9','3SBIRT Drugs','',49,31,1,0,255,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','column1','2SBIRT Alcohol','',55,31,0,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','column3','4SBIRT Tobacco','',42,31,1,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','column5','2SBIRT Alcohol','',68,31,1,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','daily_alc','2SBIRT Alcohol','2. Alcohol daily intake',25,27,1,100,255,'drinks_daily',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','daily_alc_ques','2SBIRT Alcohol','',20,31,1,0,100,'',1,0,'','','2. In the last year, when you drink alcohol, \r\nhow many drinks do you typically have on a \r\ngiven day?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','daily_alc_score','2SBIRT Alcohol','',28,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','DOB','1Patient Info','DOB',10,4,2,0,10,'',1,1,'','D','Date of Birth',0,'','D','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_men','2SBIRT Alcohol','3. 5+ drinks at 1 time',45,27,1,100,255,'sbirt_gen_freq',1,1,'','','',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:6:\"Female\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_men_ques','2SBIRT Alcohol','',40,31,1,0,100,'',1,0,'','','3. In the last year, how often have you had \r\n5 or more drinks on one occasion?',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:6:\"Female\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_men_score','2SBIRT Alcohol','',48,2,1,3,5,'',0,2,'','','',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:6:\"Female\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_women','2SBIRT Alcohol','3. 4+ drinks at 1 time',35,27,1,100,255,'sbirt_gen_freq',1,1,'','','',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:4:\"Male\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_women_ques','2SBIRT Alcohol','',30,31,1,0,100,'',1,0,'','','3. In the last year, how often have you had \r\n4 or more drinks on one occasion?',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:4:\"Male\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drinks_women_score','2SBIRT Alcohol','',38,2,1,3,5,'',0,2,'','','',0,'','F','a:1:{i:0;a:5:{s:2:\"id\";s:3:\"sex\";s:6:\"itemid\";N;s:8:\"operator\";s:2:\"eq\";s:5:\"value\";s:4:\"Male\";s:5:\"andor\";s:0:\"\";}}');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drugs_pos','3SBIRT Drugs','',50,31,2,0,255,'',1,0,'','','A score 1 or more is considered a positive screen.\r\nSo if summed score is 1 or greater, the final score = 1.',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drugs_sum','3SBIRT Drugs','Drugs Summed Score',60,2,1,5,10,'',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drug_rec','3SBIRT Drugs','Drugs Positive/Alert Provider',90,1,1,0,5,'yesno',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','drug_txt','3SBIRT Drugs','',2,31,2,0,255,'',0,0,'','','Drugs',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','men_pos','2SBIRT Alcohol','',50,31,2,0,50,'',1,0,'','','A score of 3 or more (women) or \r\n4 or more (men) is considered a positive screen.',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','misuse_rx','3SBIRT Drugs','2. Misused Prescriptions',25,27,1,100,255,'sbirt_freq',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','misuse_rx_ques','3SBIRT Drugs','',20,31,1,0,100,'',1,0,'','','2. Have you used a prescription medication for \r\nnon-medical reasons in the last year (for instance \r\nbecause of the feeling it caused or experience \r\nyou have) or in larger amounts than \r\nprescribed?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','misuse_rx_score','3SBIRT Drugs','',28,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','mj_rx','3SBIRT Drugs','If ever, do you have a medical marijuana card?',15,1,1,0,10,'yesno',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','mj_use','3SBIRT Drugs','1. Marijuana/Cannabis Use',11,27,1,100,255,'sbirt_freq',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','mj_use_ques','3SBIRT Drugs','',10,31,1,0,100,'',1,0,'','','1. Have you used marijuana or cannabis in the \r\nlast year? \r\n(Score 0 if used according to prescription)',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','mj_use_score','3SBIRT Drugs','',12,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','other_use','3SBIRT Drugs','3. Other drug use',35,27,1,100,255,'sbirt_freq',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','other_use_ques','3SBIRT Drugs','',30,31,1,0,100,'',1,0,'','','3. Have you used other drugs in the past year \r\n(for example, street heroin, salvia, inhalants, etc.)?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','other_use_score','3SBIRT Drugs','',38,2,1,3,5,'',0,2,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','sbirt_alc_text','2SBIRT Alcohol','',2,31,2,0,255,'',0,0,'','','Alcohol (AUDIT – C)',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','sex','1Patient Info','Gender',30,1,2,0,0,'sex',1,1,'','N','Gender',0,'','D','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_rec','4SBIRT Tobacco','Tobacco Positive/Alert Provider',45,1,1,0,5,'yesno',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_score','4SBIRT Tobacco','Tobacco Score',35,2,1,3,5,'',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_score_txt','4SBIRT Tobacco','',30,31,2,0,100,'',1,0,'','','A score 1 or more is considered a positive screen. So if \r\nscore is 1 or greater, the final score = 1.',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_txt','4SBIRT Tobacco','',10,31,1,0,255,'',0,0,'','','Tobacco',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_use','4SBIRT Tobacco','1. Tobacco use',25,27,1,100,255,'sbirt_tob_freq',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_use_ques','4SBIRT Tobacco','',20,31,1,0,100,'',1,0,'','','1. In the past month, how often have you used tobacco \r\nproducts?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFSBIRTScreen','tob_use_score','4SBIRT Tobacco','',28,2,1,3,5,'',0,2,'','','',0,'','F','');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-15 20:06:22
