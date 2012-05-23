SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

DROP SCHEMA IF EXISTS `kima` ;
CREATE SCHEMA IF NOT EXISTS `kima` DEFAULT CHARACTER SET utf8 ;
USE `kima` ;

-- -----------------------------------------------------
-- Table `kima`.`kima_country`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kima`.`kima_country` ;

CREATE  TABLE IF NOT EXISTS `kima`.`kima_country` (
  `id_country` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id_country`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `kima`.`kima_state`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kima`.`kima_state` ;

CREATE  TABLE IF NOT EXISTS `kima`.`kima_state` (
  `id_state` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `id_country` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id_state`) ,
  INDEX `fk_state_country` (`id_country` ASC) ,
  UNIQUE INDEX `un_state_id_country_name` (`id_country` ASC, `name` ASC) ,
  CONSTRAINT `fk_state_country`
    FOREIGN KEY (`id_country` )
    REFERENCES `kima`.`kima_country` (`id_country` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `kima`.`kima_city`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kima`.`kima_city` ;

CREATE  TABLE IF NOT EXISTS `kima`.`kima_city` (
  `id_city` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `id_state` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id_city`) ,
  INDEX `fk_city_state` (`id_state` ASC) ,
  UNIQUE INDEX `un_city_id_state_name` (`id_state` ASC, `name` ASC) ,
  CONSTRAINT `fk_city_state`
    FOREIGN KEY (`id_state` )
    REFERENCES `kima`.`kima_state` (`id_state` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `kima`.`kima_location`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kima`.`kima_location` ;

CREATE  TABLE IF NOT EXISTS `kima`.`kima_location` (
  `id_location` INT UNSIGNED NOT NULL ,
  `id_city` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id_location`) ,
  INDEX `fk_location_city` (`id_city` ASC) ,
  UNIQUE INDEX `un_location_id_city_name` (`id_city` ASC, `name` ASC) ,
  CONSTRAINT `fk_location_city`
    FOREIGN KEY (`id_city` )
    REFERENCES `kima`.`kima_city` (`id_city` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `kima`.`kima_person`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kima`.`kima_person` ;

CREATE  TABLE IF NOT EXISTS `kima`.`kima_person` (
  `id_person` INT UNSIGNED NOT NULL ,
  `id_location` INT UNSIGNED NOT NULL ,
  `genre` TINYINT(1) UNSIGNED NOT NULL ,
  `id_expiration_date` DATE NULL ,
  `name` VARCHAR(50) NOT NULL ,
  `last_name` VARCHAR(50) NOT NULL ,
  `surname` VARCHAR(50) NULL ,
  PRIMARY KEY (`id_person`) ,
  INDEX `fk_person_location` (`id_location` ASC) ,
  CONSTRAINT `fk_person_location`
    FOREIGN KEY (`id_location` )
    REFERENCES `kima`.`kima_location` (`id_location` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- procedure save_location
-- -----------------------------------------------------

USE `kima`;
DROP procedure IF EXISTS `kima`.`save_location`;

DELIMITER $$
USE `kima`$$
CREATE PROCEDURE `kima`.`save_location` (
  IN p_id_location INT,
  IN p_country_name VARCHAR(50),
  IN p_state_name VARCHAR(50),
  IN p_city_name VARCHAR(50), 
  IN p_location_name VARCHAR(50))
BEGIN

DECLARE p_id_country INT DEFAULT 0;
DECLARE p_id_state INT DEFAULT 0;
DECLARE p_id_city INT DEFAULT 0;

-- country --
INSERT INTO kima_country SET
  name=p_country_name
ON DUPLICATE KEY UPDATE
  name=p_country_name;

SELECT id_country INTO p_id_country
  FROM kima_country
  WHERE name=p_country_name;

-- state --
INSERT INTO kima_state SET
  id_country=p_id_country,
  name=p_state_name
ON DUPLICATE KEY UPDATE
  id_country=p_id_country,
  name=p_state_name;

SELECT id_state INTO p_id_state
  FROM kima_state
  WHERE id_country=p_id_country
  AND name=p_state_name;

-- city --
INSERT INTO kima_city SET
  id_state=p_id_state,
  name=p_city_name
ON DUPLICATE KEY UPDATE
  id_state=p_id_state,
  name=p_city_name;

SELECT id_city INTO p_id_city
  FROM kima_city
  WHERE id_state=p_id_state
  AND name=p_city_name;

-- location --
INSERT INTO kima_location SET
  id_location=p_id_location,
  id_city=p_id_city,
  name=p_location_name
ON DUPLICATE KEY UPDATE
  id_city=p_id_city,
  name=p_location_name;

END$$

DELIMITER ;
-- -----------------------------------------------------
-- procedure save_person
-- -----------------------------------------------------

USE `kima`;
DROP procedure IF EXISTS `kima`.`save_person`;

DELIMITER $$
USE `kima`$$
CREATE PROCEDURE `kima`.`save_person` (
  IN p_id_person INT,
  IN p_id_location INT,
  IN p_genre INT,
  IN p_id_expiration_date INT,
  IN p_name VARCHAR(50),
  IN p_last_name VARCHAR(50),
  IN p_surname VARCHAR(50))
BEGIN

INSERT INTO kima_person SET
  id_person=p_id_person,
  id_location=p_id_location,
  genre=p_genre,
  id_expiration_date=STR_TO_DATE(p_id_expiration_date, '%Y%m%d'),
  name=p_name,
  last_name=p_last_name,
  surname=p_surname
ON DUPLICATE KEY UPDATE
  id_location=p_id_location,
  genre=p_genre,
  id_expiration_date=STR_TO_DATE(p_id_expiration_date, '%Y%m%d'),
  name=p_name,
  last_name=p_last_name,
  surname=p_surname;

END$$

DELIMITER ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
