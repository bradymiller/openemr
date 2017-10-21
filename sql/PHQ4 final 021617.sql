SET character_set_client = utf8;
DELETE FROM list_options WHERE list_id = 'PHQ_score';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'PHQ_score';
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
-- WHERE:  list_id = 'lists' AND option_id = 'PHQ_score' OR list_id = 'PHQ_score'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','PHQ_score','PHQ score',303,1,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ_score','0','',50,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ_score','1','0-Not at all',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ_score','2','1-Several days',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ_score','3','2-More than half the days',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ_score','4','3-Nearly everyday',40,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:16:29
DELETE FROM list_options WHERE list_id = 'PHQ4_Severity';
DELETE FROM list_options WHERE list_id = 'lists' AND option_id = 'PHQ4_Severity';
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
-- WHERE:  list_id = 'lists' AND option_id = 'PHQ4_Severity' OR list_id = 'PHQ4_Severity'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lists','PHQ4_Severity','PHQ4 Severity',302,1,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ4_Severity','0','',50,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ4_Severity','1','0-2: None',10,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ4_Severity','2','3-5: Mild',20,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ4_Severity','3','6-8: Moderate',30,0,0,'','','',0,0,1,'');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('PHQ4_Severity','4','9-12: Severe',40,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:16:29
DELETE FROM layout_options WHERE form_id = 'LBFPHQ4';
DELETE FROM list_options WHERE list_id = 'lbfnames' AND option_id = 'LBFPHQ4';
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
-- WHERE:  list_id = 'lbfnames' AND option_id = 'LBFPHQ4'

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`) VALUES ('lbfnames','LBFPHQ4','PHQ4 Questionnaire',20,0,0,'','','',0,0,1,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:16:29
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
-- WHERE:  form_id = 'LBFPHQ4'

INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','anxious','1PHQ_4 Scoring','',40,31,1,0,100,'',1,0,'','','1. Feeling nervous, anxious or on edge',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','anx_score','1PHQ_4 Scoring','1. Anxiety score',50,1,1,0,50,'PHQ_score',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','anx_sub','1PHQ_4 Scoring','',220,31,1,0,0,'',0,0,'','','(Sum of Items 1 and 2.)',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','anx_subscore','1PHQ_4 Scoring','Anxiety Subscore (0 to 6)',210,2,1,3,5,'',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank1','1PHQ_4 Scoring','',125,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank2','1PHQ_4 Scoring','',200,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank20','1PHQ_4 Scoring','',20,31,2,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank21','1PHQ_4 Scoring','',30,31,2,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank300','1PHQ_4 Scoring','',205,31,2,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank301','1PHQ_4 Scoring','',111,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank4','1PHQ_4 Scoring','',245,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','blank7','1PHQ_4 Scoring','',250,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','depressed','1PHQ_4 Scoring','',70,31,1,0,50,'',1,0,'','','3. Feeling down, depressed, or hopeless?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','dep_blank','1PHQ_4 Scoring','',227,31,1,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','dep_score','1PHQ_4 Scoring','3. Depression score',80,1,1,0,50,'PHQ_score',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','dep_sub','1PHQ_4 Scoring','',240,31,1,0,0,'',0,0,'','','(Sum of Items 3 and 4.)',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','dep_subscore','1PHQ_4 Scoring','Despression Subscore (0 to 6)',230,2,1,3,5,'',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','interest','1PHQ_4 Scoring','4. Interest score',110,1,1,0,50,'PHQ_score',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','int_quest','1PHQ_4 Scoring','',100,31,1,0,50,'',1,0,'','','4. Little interest or pleasure in doing things?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','lead_question','1PHQ_4 Scoring','',10,31,2,0,100,'',1,0,'','','Over the last two weeks, how often have you been bothered by any of the following problems?',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','provider_ref','1PHQ_4 Scoring','Refer to Provider/IPP partner',260,1,1,0,5,'yesno',1,3,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','severity','1PHQ_4 Scoring','Severity',140,1,1,0,20,'PHQ4_Severity',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','sev_subtext','1PHQ_4 Scoring','',225,31,1,0,0,'',1,6,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','sev_subtext2','1PHQ_4 Scoring','',130,31,2,0,0,'',1,0,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','sev_text','1PHQ_4 Scoring','',115,31,2,0,0,'',1,0,'','','***Click Total Score textbox to update Scores, Severity, and whether Follow-up is needed------------------------------------->',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','sev_text3','1PHQ_4 Scoring','',255,31,2,0,0,'',1,0,'','','A Total Score of 6 or greater requires follow-up with a provider.***\r\nor\r\n***a subscore of 3 or greater on either subscore requires follow-up with a provider regardless of total PHQ4 score.***',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','total_phq4_score','1PHQ_4 Scoring','Total Score (out of 12)',120,2,1,5,10,'',1,1,'','','',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','worry','1PHQ_4 Scoring','',60,31,1,0,100,'',1,0,'','','2. Not being able to stop or control worrying',0,'','F','');
INSERT INTO `layout_options` (`form_id`, `field_id`, `group_name`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`, `default_value`, `edit_options`, `description`, `fld_rows`, `list_backup_id`, `source`, `conditions`) VALUES ('LBFPHQ4','worry_score','1PHQ_4 Scoring','2. Worry score',65,1,1,0,50,'PHQ_score',1,3,'','','',0,'','F','');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-16 11:16:29
