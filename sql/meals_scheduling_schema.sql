--
-- Table structure for table `auth_user`
--

DROP TABLE IF EXISTS `auth_user`;
CREATE TABLE `auth_user` (
  `id` int(11) DEFAULT NULL,
  `first_name` text DEFAULT NULL,
  `last_name` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `username` text DEFAULT NULL,
  `gather_id` smallint(6) DEFAULT NULL
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `schedule_comments`
--

DROP TABLE IF EXISTS `schedule_comments`;
CREATE TABLE `schedule_comments` (
  `worker_id` int(11) NOT NULL,
  `timestamp` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `avoids` varchar(500) DEFAULT NULL,
  `prefers` varchar(500) DEFAULT NULL,
  `clean_after_self` varchar(3) DEFAULT NULL,
  `bunch_shifts` varchar(2) DEFAULT NULL,
  `bundle_shifts` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`worker_id`)
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `schedule_prefs`
--

DROP TABLE IF EXISTS `schedule_prefs`;
CREATE TABLE `schedule_prefs` (
  `date_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `pref` int(11) DEFAULT NULL,
  PRIMARY KEY (`date_id`,`worker_id`)
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `schedule_shifts`
--

DROP TABLE IF EXISTS `schedule_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedule_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_shift_string` varchar(12) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `work_app_assignment`
--

DROP TABLE IF EXISTS `work_app_assignment`;
CREATE TABLE `work_app_assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(1) NOT NULL,
  `instances` smallint(5) unsigned NOT NULL,
  `job_id` int(11) NOT NULL,
  `reason_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `work_app_job`
--

DROP TABLE IF EXISTS `work_app_job`;
CREATE TABLE `work_app_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL,
  `number` int(10) unsigned NOT NULL,
  `displayOrder` int(10) unsigned NOT NULL,
  `description` varchar(40) NOT NULL,
  `instances` smallint(5) unsigned NOT NULL,
  `hoursPerInstance` decimal(10,0) NOT NULL,
  `maximumInstancesPerPerson` smallint(5) unsigned NOT NULL,
  `wikiLink` varchar(60) NOT NULL,
  `coordinator` tinyint(1) NOT NULL,
  `meeting` tinyint(1) NOT NULL,
  `email` tinyint(1) NOT NULL,
  `message` varchar(60) NOT NULL,
  `committee_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=latin1;

--
-- Table structure for table `work_app_season`
--

DROP TABLE IF EXISTS `work_app_season`;
CREATE TABLE `work_app_season` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(40) NOT NULL,
  `year` int(10) unsigned NOT NULL,
  `wasindex` smallint(5) unsigned NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `surveyStartDate` date NOT NULL,
  `surveyEndDate` date NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=latin1;

