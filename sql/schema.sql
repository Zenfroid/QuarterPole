-- Quarter Pole: MySQL schema (draft v0.1)
-- MySQL 8 / MariaDB 10.5+, InnoDB, utf8mb4.
-- Game tables use the qp_ prefix so they can share a database with SMF (smf_ tables).
-- Every player is an SMF member: member_id = smf_members.id_member.
-- Tunable numbers live in qp_settings, never in code.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Settings and game clock
-- ---------------------------------------------------------------

CREATE TABLE qp_settings (
  setting_key   VARCHAR(64)  NOT NULL PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  description   VARCHAR(255) NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row. The daily cron advances game_date by 2 days (game runs 2x).
CREATE TABLE qp_game_clock (
  id            TINYINT  NOT NULL PRIMARY KEY DEFAULT 1,
  game_date     DATE     NOT NULL,           -- current in-game date
  last_tick_at  DATETIME NULL,               -- last time the cron ran
  CONSTRAINT chk_clock_single CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Players (stables)
-- ---------------------------------------------------------------

CREATE TABLE qp_stables (
  member_id         INT UNSIGNED NOT NULL PRIMARY KEY,  -- smf_members.id_member
  stable_name       VARCHAR(80)  NOT NULL,
  cash              BIGINT       NOT NULL DEFAULT 0,    -- one account: spend and earn interest
  creation_credits  INT          NOT NULL DEFAULT 0,
  ami_points        INT          NOT NULL DEFAULT 0,    -- converts to credits at setting ami_per_credit
  premium_until     DATETIME     NULL,                  -- NULL or past = not premium
  login_streak_days INT          NOT NULL DEFAULT 0,
  last_login_date   DATE         NULL,
  forum_posts_paid  INT          NOT NULL DEFAULT 0,    -- last 50-post milestone already rewarded
  last_train_ami_date DATE       NULL,                  -- training AMI is 1 per real day, flat
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_stable_name (stable_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Every Cash movement, for auditing and the bank statement page.
CREATE TABLE qp_transactions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  member_id     INT UNSIGNED NOT NULL,
  amount        BIGINT       NOT NULL,           -- positive = in, negative = out
  balance_after BIGINT       NOT NULL,
  kind          ENUM('start','purse','entry_fee','stud_fee_in','stud_fee_out','sale','purchase',
                     'claim_in','claim_out','refund','interest','genetic_test','trainer_report','admin') NOT NULL,
  ref_table     VARCHAR(32)  NULL,               -- e.g. 'qp_races'
  ref_id        BIGINT UNSIGNED NULL,
  note          VARCHAR(255) NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_tx_member (member_id, created_at),
  CONSTRAINT fk_tx_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_ami_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  member_id   INT UNSIGNED NOT NULL,
  amount      INT          NOT NULL,
  reason      ENUM('training','race_entry','race_finish','login_streak','forum_milestone',
                   'credit_conversion','admin') NOT NULL,
  ref_id      BIGINT UNSIGNED NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_ami_member (member_id, created_at),
  CONSTRAINT fk_ami_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_subscriptions (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  member_id     INT UNSIGNED NOT NULL,
  plan          ENUM('monthly','yearly') NOT NULL,
  provider      VARCHAR(32)  NULL,               -- 'paypal' / 'stripe', decided later
  provider_ref  VARCHAR(128) NULL,
  starts_at     DATETIME     NOT NULL,
  ends_at       DATETIME     NOT NULL,
  status        ENUM('active','cancelled','expired') NOT NULL DEFAULT 'active',
  KEY ix_sub_member (member_id),
  CONSTRAINT fk_sub_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_badges (
  member_id   INT UNSIGNED NOT NULL,
  badge_code  VARCHAR(40)  NOT NULL,  -- 'longevity', 'winner', 'triple_3yo_crown', 'breeder', 'breeders_series'...
  tier        TINYINT      NOT NULL DEFAULT 1,
  earned_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id, badge_code, tier),
  CONSTRAINT fk_badge_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Horses
-- ---------------------------------------------------------------

CREATE TABLE qp_horses (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(60)  NOT NULL,
  sex              ENUM('M','F') NOT NULL,          -- colt/stallion/gelding vs filly/mare is derived from age + gelded
  gelded           TINYINT(1)   NOT NULL DEFAULT 0,
  birth_game_date  DATE         NOT NULL,           -- age = game years since this
  sire_id          INT UNSIGNED NULL,               -- NULL for foundation and ghost horses
  dam_id           INT UNSIGNED NULL,
  owner_id         INT UNSIGNED NULL,               -- NULL for ghosts and game stallions
  breeder_id       INT UNSIGNED NULL,               -- who bred it (NULL = game)
  origin           ENUM('foundation','bred','backdated','game_stallion','ghost') NOT NULL,
  status           ENUM('active','retired','pensioned','rehomed','deceased') NOT NULL DEFAULT 'active',
  offered_for_sale TINYINT(1)   NOT NULL DEFAULT 0, -- must be 1 before TRP rehoming is allowed

  -- Hidden stats, 25 to 100 unless noted. Values are the horse's PEAK.
  surface_pref     ENUM('dirt','turf','steeplechase') NOT NULL,
  running_style    ENUM('front','steady','close') NOT NULL,
  endurance_f      DECIMAL(4,2) NOT NULL,          -- furlongs
  stamina_f        DECIMAL(4,2) NOT NULL,          -- furlongs of kick (0.25 to 3.00)
  stat_break       TINYINT UNSIGNED NOT NULL,
  stat_sprint      TINYINT UNSIGNED NOT NULL,
  stat_speed       TINYINT UNSIGNED NOT NULL,
  stat_dexterity   TINYINT UNSIGNED NOT NULL,
  stat_temper      TINYINT UNSIGNED NOT NULL,
  stat_drive       TINYINT UNSIGNED NOT NULL,
  stat_track       TINYINT UNSIGNED NOT NULL,
  stat_scope       TINYINT UNSIGNED NOT NULL,
  stat_recovery    TINYINT UNSIGNED NOT NULL,      -- % Energy regained per day
  stat_potency     TINYINT UNSIGNED NOT NULL,
  energy           TINYINT UNSIGNED NOT NULL DEFAULT 100,  -- current %, not a peak stat
  peak_age         TINYINT UNSIGNED NOT NULL,      -- 2 to 5, hidden, inherited
  training_progress DECIMAL(5,2) NOT NULL DEFAULT 0,       -- 0-100 boost toward peak before peak age
  is_great         TINYINT(1)   NOT NULL DEFAULT 0,
  is_dud           TINYINT(1)   NOT NULL DEFAULT 0,
  coi              DECIMAL(6,4) NOT NULL DEFAULT 0, -- 5-generation coefficient of inbreeding

  -- Color genotype (hidden until tested). Two alleles per locus.
  gene_e           CHAR(2)      NOT NULL,          -- 'EE','Ee','ee'
  gene_a           VARCHAR(6)   NOT NULL,          -- 'A/A','A/At','At/a','a/a'...
  gene_g           CHAR(2)      NOT NULL,          -- 'GG','Gg','gg'
  gene_cr          VARCHAR(6)   NOT NULL,          -- 'Cr/Cr','Cr/n','n/n'
  gene_o           CHAR(2)      NOT NULL,          -- 'On','nn' (OO is lethal)
  gene_w5          CHAR(2)      NOT NULL,          -- 'Wn','nn'
  gene_w20         CHAR(2)      NOT NULL,          -- 'WW','Wn','nn'
  gene_w22         CHAR(2)      NOT NULL,          -- 'Wn','nn'
  color_name       VARCHAR(60)  NOT NULL,          -- display text, e.g. 'Grey (Chestnut)'
  genetics_tested  TINYINT(1)   NOT NULL DEFAULT 0,

  -- Trainer report
  report_level     ENUM('none','basic','experienced','expert') NOT NULL DEFAULT 'none',

  -- Training and stud
  last_trained_on  DATE         NULL,              -- real date; once per real day
  stud_fee         INT UNSIGNED NULL,              -- NULL = not standing at stud
  book_size        SMALLINT UNSIGNED NULL,         -- player cap 100, game studs 25
  last_foaled_game_year SMALLINT NULL,             -- mares foal once per game year

  -- Career totals (denormalized for stable cards)
  starts           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  wins             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  places           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  shows            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  earnings         BIGINT       NOT NULL DEFAULT 0,

  died_game_date   DATE         NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY ix_horse_owner (owner_id, status),
  KEY ix_horse_sire (sire_id),
  KEY ix_horse_dam (dam_id),
  KEY ix_horse_status (status),
  CONSTRAINT fk_horse_sire  FOREIGN KEY (sire_id)  REFERENCES qp_horses(id),
  CONSTRAINT fk_horse_dam   FOREIGN KEY (dam_id)   REFERENCES qp_horses(id),
  CONSTRAINT fk_horse_owner FOREIGN KEY (owner_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Static trainer notes, written once per level (an upgrade adds a row, never redoes one).
CREATE TABLE qp_trainer_reports (
  horse_id    INT UNSIGNED NOT NULL,
  level       ENUM('basic','experienced','expert') NOT NULL,
  notes       TEXT         NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (horse_id, level),
  CONSTRAINT fk_report_horse FOREIGN KEY (horse_id) REFERENCES qp_horses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trainer's report wording, editable on the admin page. Several lines for the same
-- topic and band are picked between at random. {name} is replaced with the horse's name.
CREATE TABLE qp_report_lines (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  topic  VARCHAR(32)  NOT NULL,   -- style_front, surface_turf, distance_mile, stat_break ...
  band   ENUM('exceptional','very_good','useful','ordinary','weak') NULL,  -- stats only
  line   VARCHAR(255) NOT NULL,
  KEY ix_report_topic (topic, band)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_breedings (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sire_id         INT UNSIGNED NOT NULL,
  dam_id          INT UNSIGNED NOT NULL,
  dam_owner_id    INT UNSIGNED NOT NULL,
  stud_fee_paid   INT UNSIGNED NOT NULL DEFAULT 0,  -- 0 when both horses have the same owner
  backdated       TINYINT(1)   NOT NULL DEFAULT 0,
  foal_game_year  SMALLINT     NOT NULL,            -- birth year of the foal (game year number, can be below 1 when backdated)
  bred_in_game_year SMALLINT   NOT NULL,            -- game year the breeding was booked; book sizes count this
  foal_id         INT UNSIGNED NULL,                -- NULL if born dead (lethal cross)
  outcome         ENUM('live','born_dead') NOT NULL DEFAULT 'live',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dam_year (dam_id, foal_game_year),  -- one foal per mare per game year
  KEY ix_breed_sire_year (sire_id, bred_in_game_year), -- for book size counts
  CONSTRAINT fk_breed_sire FOREIGN KEY (sire_id) REFERENCES qp_horses(id),
  CONSTRAINT fk_breed_dam  FOREIGN KEY (dam_id)  REFERENCES qp_horses(id),
  CONSTRAINT fk_breed_foal FOREIGN KEY (foal_id) REFERENCES qp_horses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Tracks, jockeys, races
-- ---------------------------------------------------------------

CREATE TABLE qp_tracks (
  id               SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(60)  NOT NULL,
  location         VARCHAR(60)  NOT NULL,
  tier             ENUM('major','turf','bread_butter','steeplechase') NOT NULL,
  has_dirt         TINYINT(1)   NOT NULL DEFAULT 0,
  has_turf         TINYINT(1)   NOT NULL DEFAULT 0,
  has_steeplechase TINYINT(1)   NOT NULL DEFAULT 0,
  -- Personality modifiers read by the race engine (all tunable)
  stretch          ENUM('short','normal','long') NOT NULL DEFAULT 'normal',
  turns            TINYINT UNSIGNED NOT NULL DEFAULT 2,
  turn_style       ENUM('tight','normal','wide','mixed') NOT NULL DEFAULT 'normal',
  jumps            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  rain_chance_pct  TINYINT UNSIGNED NOT NULL DEFAULT 15,
  condition_profile ENUM('varied','mostly_fast','wet') NOT NULL DEFAULT 'varied',
  active           TINYINT(1)   NOT NULL DEFAULT 0,
  UNIQUE KEY uq_track_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Identical stats for now; the engine reads skill so jockeys can differ later.
CREATE TABLE qp_jockeys (
  id     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name   VARCHAR(60) NOT NULL,
  skill  TINYINT UNSIGNED NOT NULL DEFAULT 50,
  active TINYINT(1) NOT NULL DEFAULT 1   -- 0 = no new rides; kept so old charts keep the name
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Named series: the five Triples and the Breeders' Stakes Series.
CREATE TABLE qp_series (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(60) NOT NULL,
  kind        ENUM('triple','breeders') NOT NULL,
  badge_code  VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Named stakes races that recur every game year. Each runs on the first Wednesday,
-- Saturday or Sunday race day on or after its game month and day. Edited on the admin page.
CREATE TABLE qp_stakes (
  id              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(80)  NOT NULL,
  track_id        SMALLINT UNSIGNED NOT NULL,
  series_id       SMALLINT UNSIGNED NULL,
  series_leg      TINYINT UNSIGNED NULL,
  race_level      ENUM('g3','g2','g1') NOT NULL,
  surface         ENUM('dirt','turf','steeplechase') NOT NULL,
  distance_f      DECIMAL(4,2) NOT NULL,
  age_min         TINYINT UNSIGNED NOT NULL,
  age_max         TINYINT UNSIGNED NULL,
  sex_rule        ENUM('open','colts_geldings','fillies_mares') NOT NULL DEFAULT 'open',
  homebred_only   TINYINT(1)   NOT NULL DEFAULT 0,
  game_month      TINYINT UNSIGNED NOT NULL,
  game_day        TINYINT UNSIGNED NOT NULL,
  weekday         ENUM('wed','sat','sun') NOT NULL,   -- Sunday is steeplechase
  purse           INT UNSIGNED NOT NULL,
  active          TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_stakes_track  FOREIGN KEY (track_id)  REFERENCES qp_tracks(id),
  CONSTRAINT fk_stakes_series FOREIGN KEY (series_id) REFERENCES qp_series(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_races (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  track_id        SMALLINT UNSIGNED NOT NULL,
  series_id       SMALLINT UNSIGNED NULL,
  series_leg      TINYINT UNSIGNED NULL,
  stakes_id       SMALLINT UNSIGNED NULL,          -- the recurring named stakes this running belongs to
  name            VARCHAR(80)  NULL,               -- stakes have names; others are 'Race 4' etc.
  race_level      ENUM('maiden','claiming','allowance','g3','g2','g1') NOT NULL,
  surface         ENUM('dirt','turf','steeplechase') NOT NULL,
  distance_f      DECIMAL(4,2) NOT NULL,           -- furlongs
  age_min         TINYINT UNSIGNED NOT NULL DEFAULT 2,
  age_max         TINYINT UNSIGNED NULL,           -- NULL = and up
  sex_rule        ENUM('open','colts_geldings','fillies_mares') NOT NULL DEFAULT 'open',
  homebred_only   TINYINT(1)   NOT NULL DEFAULT 0, -- Breeders' Series: no foundation horses
  claiming_price  INT UNSIGNED NULL,
  purse           INT UNSIGNED NOT NULL,
  entry_fee       INT UNSIGNED NOT NULL,
  run_on          DATE         NOT NULL,           -- real date it runs
  game_date       DATE         NOT NULL,
  track_condition VARCHAR(16)  NULL,               -- 'fast','good','muddy','sloppy','firm','yielding','soft'...
  status          ENUM('open','closed','run','cancelled') NOT NULL DEFAULT 'open',
  KEY ix_race_day (run_on, status),
  KEY ix_race_stakes (stakes_id, game_date),
  CONSTRAINT fk_race_track  FOREIGN KEY (track_id)  REFERENCES qp_tracks(id),
  CONSTRAINT fk_race_series FOREIGN KEY (series_id) REFERENCES qp_series(id),
  CONSTRAINT fk_race_stakes FOREIGN KEY (stakes_id) REFERENCES qp_stakes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per horse in a race, player or ghost.
CREATE TABLE qp_entries (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  race_id         INT UNSIGNED NOT NULL,
  horse_id        INT UNSIGNED NOT NULL,
  owner_id        INT UNSIGNED NULL,               -- NULL for ghosts
  jockey_id       SMALLINT UNSIGNED NOT NULL,
  post_position   TINYINT UNSIGNED NULL,
  finish_position TINYINT UNSIGNED NULL,           -- NULL = DNF / not yet run
  margin_text     VARCHAR(16)  NULL,               -- 'nose','head','neck','1 1/2'...
  final_time      DECIMAL(6,2) NULL,               -- seconds
  earnings        INT UNSIGNED NOT NULL DEFAULT 0, -- shown for ghosts, paid only to players
  fee_paid        INT UNSIGNED NOT NULL DEFAULT 0, -- refunded on a scratch
  claimed_by      INT UNSIGNED NULL,
  UNIQUE KEY uq_entry (race_id, horse_id),
  KEY ix_entry_horse (horse_id),
  CONSTRAINT fk_entry_race   FOREIGN KEY (race_id)   REFERENCES qp_races(id),
  CONSTRAINT fk_entry_horse  FOREIGN KEY (horse_id)  REFERENCES qp_horses(id),
  CONSTRAINT fk_entry_jockey FOREIGN KEY (jockey_id) REFERENCES qp_jockeys(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Claims put in on horses in claiming races. Cash is held when the claim is made;
-- after the race one claim wins (a shake if several) and the rest are refunded.
CREATE TABLE qp_claims (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entry_id    INT UNSIGNED NOT NULL,
  member_id   INT UNSIGNED NOT NULL,
  amount      INT UNSIGNED NOT NULL,
  status      ENUM('pending','won','lost','refunded') NOT NULL DEFAULT 'pending',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_claim (entry_id, member_id),
  CONSTRAINT fk_claim_entry  FOREIGN KEY (entry_id)  REFERENCES qp_entries(id),
  CONSTRAINT fk_claim_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Points of call for the result chart.
CREATE TABLE qp_race_calls (
  entry_id        INT UNSIGNED NOT NULL,
  call_point      ENUM('start','first','second','stretch','finish') NOT NULL,
  position        TINYINT UNSIGNED NOT NULL,
  lengths_behind  DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (entry_id, call_point),
  CONSTRAINT fk_call_entry FOREIGN KEY (entry_id) REFERENCES qp_entries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leader's fractional times per race.
CREATE TABLE qp_race_fractions (
  race_id     INT UNSIGNED NOT NULL,
  at_f        DECIMAL(4,2) NOT NULL,   -- furlongs from start
  time_s      DECIMAL(6,2) NOT NULL,
  PRIMARY KEY (race_id, at_f),
  CONSTRAINT fk_frac_race FOREIGN KEY (race_id) REFERENCES qp_races(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Market
-- ---------------------------------------------------------------

CREATE TABLE qp_auctions (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id        INT UNSIGNED NOT NULL,
  seller_id       INT UNSIGNED NOT NULL,
  start_price     INT UNSIGNED NOT NULL,
  reserve_price   INT UNSIGNED NULL,
  buy_now_price   INT UNSIGNED NULL,               -- not used at launch
  current_bid     INT UNSIGNED NULL,
  current_bidder  INT UNSIGNED NULL,
  starts_at       DATETIME     NOT NULL,
  ends_at         DATETIME     NOT NULL,           -- starts_at + 72 hours
  status          ENUM('live','sold','unsold','cancelled') NOT NULL DEFAULT 'live',
  KEY ix_auction_live (status, ends_at),
  CONSTRAINT fk_auction_horse  FOREIGN KEY (horse_id)  REFERENCES qp_horses(id),
  CONSTRAINT fk_auction_seller FOREIGN KEY (seller_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qp_bids (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  auction_id  INT UNSIGNED NOT NULL,
  bidder_id   INT UNSIGNED NOT NULL,
  amount      INT UNSIGNED NOT NULL,
  placed_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_bid_auction (auction_id, amount),
  CONSTRAINT fk_bid_auction FOREIGN KEY (auction_id) REFERENCES qp_auctions(id),
  CONSTRAINT fk_bid_bidder  FOREIGN KEY (bidder_id)  REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Text pages (welcome, rules, guide), edited on the admin panel.
CREATE TABLE qp_pages (
  slug        VARCHAR(20)  NOT NULL PRIMARY KEY,
  title       VARCHAR(100) NOT NULL,
  body        TEXT         NOT NULL,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
