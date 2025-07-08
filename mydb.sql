-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mydb
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class` (
  `id` int(11) NOT NULL,
  `grade` int(2) DEFAULT NULL,
  `term` varchar(10) DEFAULT NULL,
  `name` varchar(40) DEFAULT NULL,
  `category1` varchar(20) DEFAULT NULL,
  `category2` varchar(20) DEFAULT NULL,
  `category3` varchar(20) DEFAULT NULL,
  `credit` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class`
--

LOCK TABLES `class` WRITE;
/*!40000 ALTER TABLE `class` DISABLE KEYS */;
INSERT INTO `class` VALUES (1,1,'前期','プロジェクト運営と意思決定(P1)','専門科目','基幹科目','なし',2),(2,1,'前期','情報処理(P1)','教養科目','教養基礎科目','情報',2),(3,1,'前期','プロジェクト運営と意思決定(P2)','専門科目','基幹科目','なし',2),(4,1,'前期','情報処理(P2)','教養科目','教養基礎科目','情報',2),(5,1,'前期','英語理解１','教養科目','教養基礎科目','コミュニケーションスキル',1),(6,1,'前期','初年次教育','教養科目','教養基礎科目','人間力養成',1),(7,1,'前期','キャリアデザイン１','教養科目','教養基礎科目','人間力養成',1),(8,1,'前期','プロジェクトと表現技法(P1)','専門科目','基幹科目','なし',2),(9,1,'前期','プロジェクトマネジメント概論(P1)','専門科目','基幹科目','なし',2),(10,1,'前期','プロジェクトと表現技法(P2)','専門科目','基幹科目','なし',2),(11,1,'前期','プロジェクトマネジメント概論(P2)','専門科目','基幹科目','なし',2),(12,1,'前期','環境保護と法','専門科目','学部共通専門科目','エンジニアズマインドの養成',2),(13,1,'前期','日本語表現法','教養科目','教養基礎科目','コミュニケーションスキル',1),(14,1,'前期','英語表現１','教養科目','教養基礎科目','コミュニケーションスキル',1),(15,1,'前期','情報処理基礎および演習','専門科目','基礎科目','なし',4),(16,1,'前期','社会システム科学入門','専門科目','学部共通専門科目','社会システム科学の基礎',2),(17,1,'前期','学部指定科目群１','教養科目','教養共通科目','人間・社会・自然の理解',2),(18,1,'前期','基礎数学および演習','専門科目','学部共通専門科目','論理的理解の養成',4),(19,1,'前期','言語と文化１','教養科目','教養共通科目','国際理解',2),(20,1,'前期','インターンシップ概論','教養科目','基幹科目','なし',2),(21,1,'前期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(22,1,'前期','国際インターン','教養科目','教養特別科目','なし',1),(23,1,'前期','国内インターン','教養科目','教養特別科目','なし',1),(24,1,'前期','ボランティア','教養科目','教養特別科目','なし',1),(25,1,'後期','英語理解２','教養科目','教養基礎科目','コミュニケーションスキル',1),(26,1,'後期','データ構造入門','専門科目','基礎科目','なし',2),(27,1,'後期','企業と経営','専門科目','基幹科目','なし',2),(28,1,'後期','ベンチャービジネス論','専門科目','学部共通専門科目','エンジニアズマインドの養成',2),(29,1,'後期','コンピュータサイエンス入門(P1)','専門科目','基礎科目','なし',2),(30,1,'後期','コミュニケーションマネジメント(P1)','専門科目','基幹科目','なし',2),(31,1,'後期','コンピュータサイエンス入門(P2)','専門科目','基礎科目','なし',2),(32,1,'後期','コミュニケーションマネジメント(P2)','専門科目','基幹科目','なし',2),(33,1,'後期','情報リテラシ','専門科目','学部共通専門科目','エンジニアズマインドの養成',2),(34,1,'後期','英語表現2','教養科目','教養基礎科目','コミュニケーションスキル',1),(35,1,'後期','学部指定科目群１','教養科目','教養共通科目','人間・社会・自然の理解',2),(36,1,'後期','線形代数入門','専門科目','学部共通専門科目','論理的理解の養成',2),(37,1,'後期','キャリアデザイン2','教養科目','教養基礎科目','人間力養成',1),(38,1,'後期','言語と文化2','教養科目','教養共通科目','国際理解',2),(39,1,'後期','ナレッジマネジメント','専門科目','基幹科目','なし',2),(40,1,'後期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(41,1,'後期','国際インターン','教養科目','教養特別科目','なし',1),(42,1,'後期','国内インターン','教養科目','教養特別科目','なし',1),(43,1,'後期','ボランティア','教養科目','教養特別科目','なし',1),(44,2,'前期','英語理解基礎3','教養科目','教養基礎科目','コミュニケーションスキル',1),(45,2,'前期','英語表現基礎3','教養科目','教養基礎科目','コミュニケーションスキル',1),(46,2,'前期','英語理解3','教養科目','教養基礎科目','コミュニケーションスキル',1),(47,2,'前期','英語表現3','教養科目','教養基礎科目','コミュニケーションスキル',1),(48,2,'前期','英語理解発展3','教養科目','教養基礎科目','コミュニケーションスキル',1),(49,2,'前期','英語表現発展3','教養科目','教養基礎科目','コミュニケーションスキル',1),(50,2,'前期','資格試験英語A','教養科目','教養基礎科目','コミュニケーションスキル',1),(51,2,'前期','資格試験英語B','教養科目','教養基礎科目','コミュニケーションスキル',1),(52,2,'前期','異文化理解','教養科目','教養共通科目','国際理解',2),(53,2,'前期','言語と文化1','教養科目','教養共通科目','国際理解',2),(54,2,'前期','言語と文化2','教養科目','教養共通科目','国際理解',2),(55,2,'前期','教養共通授業','教養科目','教養共通科目','人間・社会・自然の理解',2),(56,2,'前期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(57,2,'前期','国際インターン','教養科目','教養特別科目','なし',1),(58,2,'前期','国内インターン','教養科目','教養特別科目','なし',1),(59,2,'前期','ボランティア','教養科目','教養特別科目','なし',1),(60,2,'前期','データ解析入門','専門科目','学部共通専門科目','理論的理解の構成',2),(61,2,'前期','科学技術者倫理','専門科目','学部共通専門科目','エンジニアズマインドの養成',2),(62,2,'前期','企業の法的環境','専門科目','学部共通専門科目','社会システム科学の基礎',2),(63,2,'前期','社会システムと意思決定','専門科目','学部共通専門科目','社会システム科学の基礎',2),(64,2,'前期','プログラム言語基礎','専門科目','基礎科目','なし',2),(65,2,'前期','プログラム言語とプログラミング','専門科目','基礎科目','なし',2),(66,2,'前期','数学','専門科目','基礎科目','なし',2),(67,2,'前期','微分方程式','専門科目','基礎科目','なし',2),(68,2,'前期','情報システム基礎','専門科目','基礎科目','なし',2),(69,2,'前期','代数学1','専門科目','基礎科目','なし',2),(70,2,'前期','プロジェクトリスク管理','専門科目','基幹科目','なし',2),(71,2,'前期','コストマネジメント','専門科目','基幹科目','なし',2),(72,2,'前期','情報とセキュリティ','専門科目','展開科目','なし',2),(73,2,'前期','創造技法','専門科目','展開科目','なし',2),(74,2,'前期','ユーザビリティエンジニアリング','専門科目','展開科目','なし',2),(75,2,'後期','英語理解基礎4','教養科目','教養基礎科目','コミュニケーションスキル',1),(76,2,'後期','英語表現基礎4','教養科目','教養基礎科目','コミュニケーションスキル',1),(77,2,'後期','英語理解3','教養科目','教養基礎科目','コミュニケーションスキル',1),(78,2,'後期','英語表現4','教養科目','教養基礎科目','コミュニケーションスキル',1),(79,2,'後期','英語理解発展4','教養科目','教養基礎科目','コミュニケーションスキル',1),(80,2,'後期','英語表現発展4','教養科目','教養基礎科目','コミュニケーションスキル',1),(81,2,'後期','資格試験英語A','教養科目','教養基礎科目','コミュニケーションスキル',1),(82,2,'後期','資格試験英語B','教養科目','教養基礎科目','コミュニケーションスキル',1),(83,2,'後期','課題研究室セミナー','教養科目','教養基礎科目','総合',2),(84,2,'後期','総合学際科目','教養科目','教養基礎科目','総合',2),(85,2,'後期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(86,2,'後期','国際インターン','教養科目','教養特別科目','なし',1),(87,2,'後期','国内インターン','教養科目','教養特別科目','なし',1),(88,2,'後期','ボランティア','教養科目','教養特別科目','なし',1),(89,2,'後期','データマイニング入門','専門科目','学部共通専門科目','理論的理解の構成',2),(90,2,'後期','オペレーションズリサーチ入門','専門科目','学部共通専門科目','理論的理解の構成',2),(91,2,'後期','ビジネスコミュニケーション','専門科目','学部共通専門科目','社会システム科学の基礎',2),(92,2,'後期','フィールドアクティビティ','専門科目','学部共通専門科目','社会システム科学の基礎',2),(93,2,'後期','プログラム言語応用','専門科目','基礎科目','なし',2),(94,2,'後期','微分方程式','専門科目','基礎科目','なし',2),(95,2,'後期','代数学2','専門科目','基礎科目','なし',2),(96,2,'後期','情報ネットワーク','専門科目','基礎科目','なし',2),(97,2,'後期','プロジェクトリスク管理','専門科目','基幹科目','なし',2),(98,2,'後期','プロジェクト計画','専門科目','基幹科目','なし',2),(99,2,'後期','コストマネジメント','専門科目','基幹科目','なし',2),(100,2,'後期','品質マネジメント','専門科目','基幹科目','なし',2),(101,2,'後期','マルチメディアシステム概論','専門科目','展開科目','なし',2),(102,2,'後期','プロジェクトと企業行動','専門科目','展開科目','なし',2),(103,2,'後期','スケジュール技法','専門科目','展開科目','なし',2),(104,2,'後期','ユーザビリティエンジニアリング','専門科目','展開科目','なし',2),(105,2,'後期','プロジェクトマネジメント実験','専門科目','展開科目','なし',4),(106,3,'前期','サービスマネジメント','専門科目','展開科目','なし',2),(107,3,'前期','技術営業論','専門科目','展開科目','なし',2),(108,3,'前期','プロジェクト評価','専門科目','展開科目','なし',2),(109,3,'前期','ユーザ要求とシステム設定','専門科目','展開科目','なし',2),(110,3,'前期','サイバーマニュファクチャアリング','専門科目','展開科目','なし',2),(111,3,'前期','ソフトウェア開発管理','専門科目','展開科目','なし',2),(112,3,'前期','教養共通科目学部指定科目群2','教養科目','教養共通科目','人間・社会・自然の理解',2),(113,3,'前期','コンピュータネットワークとアプリケーション','専門科目','展開科目','なし',2),(114,3,'前期','数理計画','専門科目','展開科目','なし',2),(115,3,'前期','ものづくりマネジメント','専門科目','発展科目','なし',2),(116,3,'前期','研究開発技法','専門科目','発展科目','なし',2),(117,3,'前期','キャリアデザイン3','教養科目','教養基礎科目','人間力養成',1),(118,3,'前期','社会技術概論','専門科目','展開科目','なし',2),(119,3,'前期','プロジェクトエンジニアリング','専門科目','基幹科目','なし',2),(120,3,'前期','プロジェクトとシステム構築','専門科目','基幹科目','なし',2),(121,3,'前期','プロジェクトマネジメント演習','専門科目','展開科目','なし',2),(122,3,'前期','ゼミナール1','専門科目','発展科目','なし',2),(123,3,'前期','イングリッシュアクティブラーニング3','教養科目','教養特別科目','なし',1),(124,3,'前期','スポーツアクティブラーニング','教養科目','教養特別科目','なし',2),(125,3,'前期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(126,3,'前期','国際インターン','教養科目','教養特別科目','なし',1),(127,3,'前期','国内インターン','教養科目','教養特別科目','なし',1),(128,3,'前期','ボランティア','教養科目','教養特別科目','なし',2),(129,3,'前期','スポーツ科学','教養科目','教養基礎科目','人間力養成',2),(130,3,'前期','総合科学特論','教養科目','教養特別科目','なし',2),(131,3,'後期','プロジェクト戦略と事業企画','専門科目','展開科目','なし',2),(132,3,'後期','ソフトウェア開発の定量化技法','専門科目','展開科目','なし',2),(133,3,'後期','生産システムマネジメント','専門科目','展開科目','なし',2),(134,3,'後期','モデリングとシュミレーション','専門科目','展開科目','なし',2),(135,3,'後期','産官学連携ビジネス創成論','専門科目','基幹科目','なし',2),(136,3,'後期','情報システム開発','専門科目','展開科目','なし',2),(137,3,'後期','グローバル時代の法','教養科目','教養共通科目','国際理解',2),(138,3,'後期','国際社会論','教養科目','教養共通科目','国際理解',2),(139,3,'後期','情報技術社会論','専門科目','展開科目','なし',2),(140,3,'後期','教養共通科目学部指定科目群2','教養科目','教養共通科目','人間・社会・自然の理解',2),(141,3,'後期','プロジェクトとシステム運用','専門科目','基幹科目','なし',2),(142,3,'後期','経営戦略','専門科目','展開科目','なし',2),(143,3,'後期','経営システム工学','専門科目','展開科目','なし',2),(144,3,'後期','課題研究','専門科目','発展科目','なし',2),(145,3,'後期','ゼミナール2','専門科目','発展科目','なし',2),(146,3,'後期','フィールドアクティビティ','専門科目','学部共通専門科目','社会システム科学の基礎',2),(147,3,'後期','スポーツアクティブラーニング','教養科目','教養特別科目','なし',2),(148,3,'後期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(149,3,'後期','国際インターン','教養科目','教養特別科目','なし',1),(150,3,'後期','国内インターン','教養科目','教養特別科目','なし',1),(151,3,'後期','ボランティア','教養科目','教養特別科目','なし',1),(152,3,'後期','スポーツ科学','教養科目','教養基礎科目','人間力養成',2),(153,3,'後期','総合科学特論','教養科目','教養特別科目','なし',2),(154,4,'前期','卒業研究','専門科目','発展科目','なし',5),(155,4,'前期','イングリッシュアクティブラーニング3','教養科目','教養特別科目','なし',1),(156,4,'前期','スポーツアクティブラーニング','教養科目','教養特別科目','なし',2),(157,4,'前期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(158,4,'前期','国際インターン','教養科目','教養特別科目','なし',1),(159,4,'前期','国内インターン','教養科目','教養特別科目','なし',1),(160,4,'前期','ボランティア','教養科目','教養特別科目','なし',1),(161,4,'前期','スポーツ科学','教養科目','教養基礎科目','人間力養成',2),(162,4,'前期','総合科学特論','教養科目','教養特別科目','なし',2),(163,4,'後期','卒業研究','専門科目','発展科目','なし',5),(164,4,'後期','スポーツアクティブラーニング','教養科目','教養特別科目','なし',2),(165,4,'後期','ソーシャルアクティブラーニング','教養科目','教養特別科目','なし',1),(166,4,'後期','国際インターン','教養科目','教養特別科目','なし',1),(167,4,'後期','国内インターン','教養科目','教養特別科目','なし',1),(168,4,'後期','ボランティア','教養科目','教養特別科目','なし',1),(169,4,'後期','スポーツ科学','教養科目','教養基礎科目','人間力養成',2),(170,4,'後期','総合科学特論','教養科目','教養特別科目','なし',2);
/*!40000 ALTER TABLE `class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_requirements`
--

DROP TABLE IF EXISTS `graduation_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `graduation_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(50) NOT NULL,
  `total_required_credits` int(11) NOT NULL,
  `required_major_credits` int(11) NOT NULL,
  `required_liberal_arts_credits` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `department` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graduation_requirements`
--

LOCK TABLES `graduation_requirements` WRITE;
/*!40000 ALTER TABLE `graduation_requirements` DISABLE KEYS */;
INSERT INTO `graduation_requirements` VALUES (1,'プロジェクトマネジメント学科',124,88,36,'2025-06-27 07:58:21');
/*!40000 ALTER TABLE `graduation_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mytable`
--

DROP TABLE IF EXISTS `mytable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mytable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foo` varchar(100) DEFAULT NULL,
  `bar` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mytable`
--

LOCK TABLES `mytable` WRITE;
/*!40000 ALTER TABLE `mytable` DISABLE KEYS */;
INSERT INTO `mytable` VALUES (2,'い',-200),(3,'う',300);
/*!40000 ALTER TABLE `mytable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productinfo`
--

DROP TABLE IF EXISTS `productinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productname` varchar(30) NOT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `imgfile` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productinfo`
--

LOCK TABLES `productinfo` WRITE;
/*!40000 ALTER TABLE `productinfo` DISABLE KEYS */;
INSERT INTO `productinfo` VALUES (1,'イヤホン',1500,100,'product6.png'),(2,'モバイルバッテリ',3980,10,NULL),(3,' USB-TypeC接続ケーブル',800,50,NULL),(4,'apple',1000,200,NULL),(5,'back',5555555,5555,NULL),(6,'jgpjpqp',1090915,55,NULL);
/*!40000 ALTER TABLE `productinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_id` (`class_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `table1`
--

DROP TABLE IF EXISTS `table1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` varchar(40) NOT NULL,
  `cost` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table1`
--

LOCK TABLES `table1` WRITE;
/*!40000 ALTER TABLE `table1` DISABLE KEYS */;
INSERT INTO `table1` VALUES (1,'A',1280,1),(2,'B',2980,0),(3,'C',198,3),(4,'D',3980,5),(5,'E',990,121),(6,'F',1500,100),(7,'G',1980,52),(8,'H',256,22),(9,'I',512,27),(10,'J',3333,4);
/*!40000 ALTER TABLE `table1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_timetables`
--

DROP TABLE IF EXISTS `user_timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_timetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `timetable_grade` int(1) DEFAULT 1,
  `timetable_term` varchar(10) DEFAULT '前期',
  `class_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `period` int(2) NOT NULL,
  `grade` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`day`,`period`,`grade`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `user_timetables_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_timetables_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_timetables`
--

LOCK TABLES `user_timetables` WRITE;
/*!40000 ALTER TABLE `user_timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` int(7) NOT NULL,
  `department` char(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-08 13:17:07
