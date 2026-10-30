-- Quarter Pole: starting data (draft v0.1). Run after schema.sql.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Settings: every tunable number from the design doc
-- ---------------------------------------------------------------
INSERT INTO qp_settings (setting_key, setting_value, description) VALUES
-- Site
('site_url',                  'https://example.com', 'Change when the domain is bought'),
('forum_path',                '/Forum',  'Where SMF lives, relative to site_url'),
('game_speed',                '2',       'Game days per real day'),
-- Economy
('starting_cash',             '150000',  'Cash for a new stable'),
('starting_credits',          '10',      'Creation credits for a new stable'),
('grand_opening_credits',     '10',      'Bonus credits during the grand opening'),
('entry_fee_pct',             '5',       'Entry fee as % of purse (max 10)'),
('purse_split',               '60,20,11,6,3', '% of purse for 1st to 5th'),
('genetic_test_cost',         '200',     'Cash'),
('report_cost_experienced',   '500',     'Cash'),
('report_cost_expert',        '1000',    'Cash'),
('bank_interest_pct',         '3',       'Monthly interest, regular players'),
('bank_interest_pct_premium', '6',       'Monthly interest, Premium'),
('bank_interest_cap',         '100000',  'Max interest paid per real month'),
('premium_discount_pct',      '10',      'Premium discount on game stud fees and entries'),
-- AMI and credits
('ami_per_credit',            '50',      'AMI needed for 1 creation credit'),
('ami_training_daily',        '1',       'Flat AMI per real day for training any horse'),
('ami_race_entry',            '1',       'AMI per horse entered'),
('ami_finish',                '5,4,3,2,1', 'Bonus AMI for 1st to 5th'),
('ami_login_streak_days',     '7',       'Days in a login streak'),
('ami_login_streak',          '1',       'AMI per completed streak'),
('ami_forum_every_posts',     '50',      'Forum milestone size'),
('ami_forum_milestone',       '5',       'AMI per forum milestone'),
-- Breeding
('swing_min',                 '-8',      'Foal stat swing low end'),
('swing_max',                 '10',      'Foal stat swing high end'),
('swing_endurance_f',         '3',       'Endurance swing, +/- furlongs'),
('swing_stamina_f',           '1',       'Stamina swing, +/- furlongs'),
('greatness_pct',             '3',       'Chance per foal (2 to 5)'),
('greatness_boost_max',       '20',      'Max boost per stat'),
('greatness_floor',           '85',      'Boosted stats land at or above this'),
('dud_pct',                   '1',       'Base dud chance'),
('dud_drop_min',              '10',      ''),
('dud_drop_max',              '20',      ''),
('dud_pct_first_cousin',      '3',       'COI around 6.25%'),
('dud_pct_half_sibling',      '8',       'COI around 12.5%'),
('dud_pct_severe',            '15',      'COI 25% or more'),
('surface_mutation_pct',      '12',      'Chance a foal prefers a different surface (10 to 15)'),
('book_cap_player',           '100',     'Max mares per player stallion per game year'),
('book_cap_game',             '25',      'Max mares per game stallion per game year'),
('mare_breeding_age',         '4',       ''),
('stallion_breeding_age',     '3',       ''),
('training_progress_per_session', '0.25', 'Training progress points per Train; 100 = full peak'),
('running_style_lean',        '10',      'Foundation stat lean by running style'),
-- Aging
('peak_years',                '2',       'Game years a horse stays at peak'),
('old_age_start',             '25',      'Death risk begins'),
('old_age_max',               '32',      'Death certain by'),
-- Racing
('min_field_size',            '5',       'Ghosts fill races up to this'),
('race_noise_pct',            '2',       'Engine: random speed swing per 1/16 mile (1 sd, %)'),
('race_form_pct',             '2',       'Engine: random form per race (1 sd, %); tuned so the best horse wins about half the time'),
('race_off_surface_pct',      '5',       'Engine: speed penalty on a non-preferred surface'),
('race_stretch_effect_pct',   '3',       'Engine: how much a long stretch helps closers and a short one front runners'),
('max_field_size',            '12',      'Most horses in one race'),
('energy_race_base',          '10',      'Energy used by any race (%)'),
('energy_race_per_furlong',   '4',       'Extra Energy used per furlong raced (%)'),
('energy_race_max',           '60',      'Most Energy one race can use (%)'),
('recovery_divisor',          '5',       'Energy regained per day = Recovery stat / this (25 = 5%, 100 = 20%)'),
('schedule_days_ahead',       '7',       'Real days of race cards kept open for entries'),
('races_per_card',            '6',       'Races per track per race day'),
('purse_maiden',              '3000,10000', 'Purse range (Cash)'),
('purse_claiming',            '3000,10000', 'Purse range (Cash)'),
('purse_allowance',           '6000,15000', 'Purse range (Cash)'),
('claiming_prices',           '5000,7500,10000,15000,20000,25000', 'Claiming prices a race can carry'),
('ghost_stat_drop_max',       '10',      'Ghost horses roll foundation stats minus up to this much'),
('race_segment_f',            '0.5',     'Engine step in furlongs (1/16 mile)'),
('trp_credit_refund',         '1',       'Credits returned when a horse is rehomed'),
('auction_hours',             '72',      '');

-- ---------------------------------------------------------------
-- Game clock: game year 1 is stored as 2001 (game year = YEAR(game_date) - 2000),
-- because MySQL only officially supports DATE years 1000 to 9999.
-- ---------------------------------------------------------------
INSERT INTO qp_game_clock (id, game_date) VALUES (1, '2001-01-01');

-- ---------------------------------------------------------------
-- Tracks (active = 1 for the four launch tracks)
-- ---------------------------------------------------------------
INSERT INTO qp_tracks
  (name, location, tier, has_dirt, has_turf, has_steeplechase, stretch, turns, turn_style, jumps, rain_chance_pct, condition_profile, active) VALUES
('Morning Star Downs', 'Knoxville, TN',  'major',        1, 1, 0, 'normal', 2, 'normal', 0,  20, 'varied',      1),
('Blackstone Downs',   'Boston, MA',     'major',        1, 0, 0, 'short',  2, 'normal', 0,  20, 'varied',      1),
('Riverside Downs',    'Madison, WI',    'major',        1, 1, 0, 'long',   2, 'normal', 0,  20, 'varied',      1),
('Beach Side',         'San Diego, CA',  'major',        1, 1, 0, 'short',  2, 'normal', 0,   5, 'mostly_fast', 0),
('European Downs',     'Europe',         'turf',         0, 1, 0, 'normal', 3, 'wide',   0,  30, 'varied',      0),
('Gulf Coast Park',    'Houston, TX',    'bread_butter', 1, 1, 0, 'normal', 2, 'normal', 0,  45, 'wet',         0),
('Iain Mac Downs',     'Birmingham, AL', 'bread_butter', 1, 1, 0, 'normal', 2, 'tight',  0,  20, 'varied',      0),
('Boudicca Park',      'Scotland',       'steeplechase', 0, 0, 1, 'normal', 2, 'mixed', 12,  35, 'varied',      1);

-- ---------------------------------------------------------------
-- Series
-- ---------------------------------------------------------------
INSERT INTO qp_series (name, kind, badge_code) VALUES
('3-year-old Triple Crown',       'triple',   'triple_3yo_crown'),
('3-year-old Triple Tiara',       'triple',   'triple_3yo_tiara'),
('2-year-old Triple Crown',       'triple',   'triple_2yo_crown'),
('2-year-old Triple Tiara',       'triple',   'triple_2yo_tiara'),
('Steeplechase Triple',           'triple',   'triple_steeplechase'),
('Breeders'' Stakes Series',      'breeders', 'breeders_series');

-- ---------------------------------------------------------------
-- Jockeys: a small pool, all identical for now
-- ---------------------------------------------------------------
INSERT INTO qp_jockeys (name, skill) VALUES
('A. Moreno', 50), ('B. Kincaid', 50), ('C. Delacroix', 50), ('D. Okafor', 50),
('E. Lindqvist', 50), ('F. Ramirez', 50), ('G. Whitlock', 50), ('H. Nakamura', 50),
('I. Castellano', 50), ('J. Byrne', 50), ('K. Adeyemi', 50), ('L. Sorensen', 50);
