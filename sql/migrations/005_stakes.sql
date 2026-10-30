-- For databases created before the stakes calendar.
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

ALTER TABLE qp_races
  ADD COLUMN stakes_id SMALLINT UNSIGNED NULL AFTER series_leg,
  ADD KEY ix_race_stakes (stakes_id, game_date),
  ADD CONSTRAINT fk_race_stakes FOREIGN KEY (stakes_id) REFERENCES qp_stakes(id);

INSERT INTO qp_stakes (name, track_id, series_id, series_leg, race_level, surface, distance_f, age_min, age_max, sex_rule, game_month, game_day, weekday, purse) VALUES
('Morning Star Derby', (SELECT id FROM qp_tracks WHERE name = 'Morning Star Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_crown'), 1, 'g1', 'dirt', 10, 3, 3, 'open', 5, 1, 'sat', 300000),
('The Blackstone', (SELECT id FROM qp_tracks WHERE name = 'Blackstone Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_crown'), 2, 'g1', 'dirt', 9.5, 3, 3, 'open', 5, 15, 'sat', 275000),
('Riverside Stakes', (SELECT id FROM qp_tracks WHERE name = 'Riverside Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_crown'), 3, 'g1', 'dirt', 12, 3, 3, 'open', 6, 8, 'sat', 300000),
('Morning Star Oaks', (SELECT id FROM qp_tracks WHERE name = 'Morning Star Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_tiara'), 1, 'g1', 'dirt', 9, 3, 3, 'fillies_mares', 4, 25, 'wed', 275000),
('The Horizon', (SELECT id FROM qp_tracks WHERE name = 'Blackstone Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_tiara'), 2, 'g1', 'dirt', 9, 3, 3, 'fillies_mares', 5, 9, 'wed', 250000),
('Cypress Oaks', (SELECT id FROM qp_tracks WHERE name = 'Riverside Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_3yo_tiara'), 3, 'g1', 'dirt', 10, 3, 3, 'fillies_mares', 7, 1, 'sat', 275000),
('Pagan Rites Futurity', (SELECT id FROM qp_tracks WHERE name = 'Morning Star Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_crown'), 1, 'g2', 'dirt', 7, 2, 2, 'colts_geldings', 8, 1, 'sat', 100000),
('Ashborne Juvenile', (SELECT id FROM qp_tracks WHERE name = 'Blackstone Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_crown'), 2, 'g1', 'dirt', 8, 2, 2, 'colts_geldings', 9, 8, 'sat', 250000),
('Timberline Futurity', (SELECT id FROM qp_tracks WHERE name = 'Riverside Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_crown'), 3, 'g1', 'dirt', 8.5, 2, 2, 'colts_geldings', 10, 8, 'sat', 275000),
('Vibrant Debutant', (SELECT id FROM qp_tracks WHERE name = 'Morning Star Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_tiara'), 1, 'g2', 'dirt', 7, 2, 2, 'fillies_mares', 8, 1, 'wed', 100000),
('Fairhaven Juvenile Fillies', (SELECT id FROM qp_tracks WHERE name = 'Blackstone Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_tiara'), 2, 'g1', 'dirt', 8, 2, 2, 'fillies_mares', 9, 8, 'wed', 250000),
('Ravenna Debutant', (SELECT id FROM qp_tracks WHERE name = 'Riverside Downs'), (SELECT id FROM qp_series WHERE badge_code = 'triple_2yo_tiara'), 3, 'g1', 'dirt', 8.5, 2, 2, 'fillies_mares', 10, 8, 'wed', 275000),
('Platinum Cup', (SELECT id FROM qp_tracks WHERE name = 'Boudicca Park'), (SELECT id FROM qp_series WHERE badge_code = 'triple_steeplechase'), 1, 'g1', 'steeplechase', 24, 4, NULL, 'open', 4, 8, 'sun', 275000),
('Governor''s Cup', (SELECT id FROM qp_tracks WHERE name = 'Boudicca Park'), (SELECT id FROM qp_series WHERE badge_code = 'triple_steeplechase'), 2, 'g1', 'steeplechase', 21, 4, NULL, 'open', 6, 8, 'sun', 250000),
('Heather Cup', (SELECT id FROM qp_tracks WHERE name = 'Boudicca Park'), (SELECT id FROM qp_series WHERE badge_code = 'triple_steeplechase'), 3, 'g1', 'steeplechase', 32, 4, NULL, 'open', 9, 8, 'sun', 300000);

INSERT IGNORE INTO qp_settings (setting_key, setting_value, description) VALUES
('stakes_per_flat_day',       '4',       'Graded stakes per Wednesday and Saturday, named ones included'),
('stakes_per_sunday',         '2',       'Graded steeplechases per Sunday, named ones included'),
('stakes_g1_day_pct',         '33',      'Chance a stakes day gets an extra unnamed Grade 1'),
('stakes_g2_pct',             '40',      'Share of other unnamed stakes that are Grade 2 (the rest are Grade 3)'),
('purse_g3',                  '20000,40000', 'Purse range (Cash)'),
('purse_g2',                  '50000,100000', 'Purse range (Cash)'),
('purse_g1',                  '150000,300000', 'Purse range (Cash)');
