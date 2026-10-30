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
('stakes_per_flat_day',       '4',       'Graded stakes per Wednesday and Saturday, named ones included'),
('stakes_per_sunday',         '2',       'Graded steeplechases per Sunday, named ones included'),
('stakes_g1_day_pct',         '33',      'Chance a stakes day gets an extra unnamed Grade 1'),
('stakes_g2_pct',             '40',      'Share of other unnamed stakes that are Grade 2 (the rest are Grade 3)'),
('purse_g3',                  '20000,40000', 'Purse range (Cash)'),
('purse_g2',                  '50000,100000', 'Purse range (Cash)'),
('purse_g1',                  '150000,300000', 'Purse range (Cash)'),
('claiming_prices',           '5000,7500,10000,15000,20000,25000', 'Claiming prices a race can carry'),
('ghost_stat_drop_max',       '10',      'Ghost horses roll foundation stats minus up to this much'),
('race_segment_f',            '0.5',     'Engine step in furlongs (1/16 mile)'),
('trp_credit_refund',         '1',       'Credits returned when a horse is rehomed'),
('auction_hours',             '72',      '');

-- ---------------------------------------------------------------
-- Trainer's report wording (edit on the admin page)
-- ---------------------------------------------------------------
INSERT INTO qp_report_lines (topic, band, line) VALUES
('style_front', NULL, 'Likes to go to the front and set the pace.'),
('style_steady', NULL, 'Settles mid-pack and runs an even race.'),
('style_close', NULL, 'Likes to sit back and make one big run late.'),
('surface_dirt', NULL, 'Prefers the dirt.'),
('surface_turf', NULL, 'Prefers the turf.'),
('surface_steeplechase', NULL, 'Prefers jumping.'),
('distance_sprint', NULL, 'Looks like a sprinter.'),
('distance_mile', NULL, 'Should handle a mile or so.'),
('distance_classic', NULL, 'Should get a mile and a quarter.'),
('distance_route', NULL, 'Will stay a long way.'),
('distance_marathon', NULL, 'Built to stay all day over jumps.'),
('stat_break', 'exceptional', 'Exceptional out of the gate.'),
('stat_break', 'very_good', 'Very good out of the gate.'),
('stat_break', 'useful', 'Useful out of the gate.'),
('stat_break', 'ordinary', 'Ordinary out of the gate.'),
('stat_break', 'weak', 'Weak out of the gate.'),
('stat_sprint', 'exceptional', 'Exceptional when asked to kick.'),
('stat_sprint', 'very_good', 'Very good when asked to kick.'),
('stat_sprint', 'useful', 'Useful when asked to kick.'),
('stat_sprint', 'ordinary', 'Ordinary when asked to kick.'),
('stat_sprint', 'weak', 'Weak when asked to kick.'),
('stat_speed', 'exceptional', 'Exceptional at cruising pace.'),
('stat_speed', 'very_good', 'Very good at cruising pace.'),
('stat_speed', 'useful', 'Useful at cruising pace.'),
('stat_speed', 'ordinary', 'Ordinary at cruising pace.'),
('stat_speed', 'weak', 'Weak at cruising pace.'),
('stat_dexterity', 'exceptional', 'Exceptional through the turns and in traffic.'),
('stat_dexterity', 'very_good', 'Very good through the turns and in traffic.'),
('stat_dexterity', 'useful', 'Useful through the turns and in traffic.'),
('stat_dexterity', 'ordinary', 'Ordinary through the turns and in traffic.'),
('stat_dexterity', 'weak', 'Weak through the turns and in traffic.'),
('stat_temper', 'exceptional', 'Exceptional in temperament.'),
('stat_temper', 'very_good', 'Very good in temperament.'),
('stat_temper', 'useful', 'Useful in temperament.'),
('stat_temper', 'ordinary', 'Ordinary in temperament.'),
('stat_temper', 'weak', 'Weak in temperament.'),
('stat_drive', 'exceptional', 'Exceptional in will to win.'),
('stat_drive', 'very_good', 'Very good in will to win.'),
('stat_drive', 'useful', 'Useful in will to win.'),
('stat_drive', 'ordinary', 'Ordinary in will to win.'),
('stat_drive', 'weak', 'Weak in will to win.'),
('stat_track', 'exceptional', 'Exceptional on an off track.'),
('stat_track', 'very_good', 'Very good on an off track.'),
('stat_track', 'useful', 'Useful on an off track.'),
('stat_track', 'ordinary', 'Ordinary on an off track.'),
('stat_track', 'weak', 'Weak on an off track.'),
('stat_scope', 'exceptional', 'Exceptional over fences.'),
('stat_scope', 'very_good', 'Very good over fences.'),
('stat_scope', 'useful', 'Useful over fences.'),
('stat_scope', 'ordinary', 'Ordinary over fences.'),
('stat_scope', 'weak', 'Weak over fences.'),
('stat_recovery', 'exceptional', 'Exceptional at bouncing back after a race.'),
('stat_recovery', 'very_good', 'Very good at bouncing back after a race.'),
('stat_recovery', 'useful', 'Useful at bouncing back after a race.'),
('stat_recovery', 'ordinary', 'Ordinary at bouncing back after a race.'),
('stat_recovery', 'weak', 'Weak at bouncing back after a race.'),
('stat_potency', 'exceptional', 'Exceptional as a prospect for breeding.'),
('stat_potency', 'very_good', 'Very good as a prospect for breeding.'),
('stat_potency', 'useful', 'Useful as a prospect for breeding.'),
('stat_potency', 'ordinary', 'Ordinary as a prospect for breeding.'),
('stat_potency', 'weak', 'Weak as a prospect for breeding.');

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
-- Named stakes: the five Triples. Add the rest on the admin page.
-- ---------------------------------------------------------------
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

-- ---------------------------------------------------------------
-- Jockeys: all identical for now. Admins add, rename and retire them on the Jockeys page.
-- ---------------------------------------------------------------
INSERT INTO qp_jockeys (name, skill) VALUES
('A. Moreno', 50), ('B. Kincaid', 50), ('C. Delacroix', 50), ('D. Okafor', 50),
('E. Lindqvist', 50), ('F. Ramirez', 50), ('G. Whitlock', 50), ('H. Nakamura', 50),
('I. Castellano', 50), ('J. Byrne', 50), ('K. Adeyemi', 50), ('L. Sorensen', 50),
('M. Fitzgerald', 50), ('N. Haddad', 50), ('O. Petrova', 50), ('P. Gallagher', 50),
('R. Ortega', 50), ('S. Mbeki', 50), ('T. Halloran', 50), ('V. Rossi', 50),
('W. Achterberg', 50), ('Y. Tanaka', 50), ('Z. Novak', 50), ('A. Beaumont', 50),
('C. MacLeod', 50), ('D. Quintero', 50), ('E. Varga', 50), ('G. Oyelaran', 50),
('J. Thibodeaux', 50), ('L. Harrington', 50);

-- ---------------------------------------------------------------
-- Welcome, rules and guide pages (edited on the admin panel)
-- ---------------------------------------------------------------
INSERT INTO qp_pages (slug, title, body) VALUES
('welcome', 'Welcome to Quarter Pole', 'Breed better racehorses from foundation stock, race them for Cash and glory, and build a stable people talk about.

## What you do
- Create foundation horses with your creation credits, or claim them from other stables.
- Train them, read your trainer''s reports, and find the races that suit them.
- Race on dirt, turf and over jumps at tracks with their own personalities.
- Retire your best runners to breeding, and breed the next champion.

## Game time
The game runs at twice real speed: every real day is two game days, and every horse has its birthday on the first of January. Races run once a day.

[Read the rules](?page=rules) or [follow the guide](?page=guide) to get started.

Join the conversation on the [forum](/Forum).'),
('rules', 'Rules', '## Your stable
- Every player gets one stable. New stables start with Cash and creation credits for foundation horses.
- Cash is the game''s money. You earn it from purses and spend it on entry fees, stud fees and services.
- Creation credits make new foundation horses. Sending a horse to the TRP gives a credit back.

## Horses
- Each horse has hidden stats. You never see the numbers: your trainer''s reports describe them, and better trainers judge more accurately.
- Every horse prefers one surface: dirt, turf or steeplechase. Horses only enter races on their own surface.
- Horses grow stronger until their peak age, hold their peak for a few years, then slowly decline.
- Energy goes down when a horse races and comes back a little each day. Tired horses run worse.
- Horse names follow the Jockey Club rule: up to 18 characters, letters, numbers, spaces and '' . -

## Racing
- Races run once a day. Enter before race time; you can scratch and get your entry fee back until then.
- A horse can run in only one race per day.
- Race types: maiden (horses that have never won), claiming, allowance, and graded stakes (Grade 3, Grade 2 and Grade 1).
- Steeplechases are for 4-year-olds and up.
- Races with no player horses are cancelled. Fields with few entries are filled out with house horses, whose purses are never paid.
- The top five finishers share the purse.

## Claiming races
- Entering a claiming race puts your horse up for sale at the race''s price.
- Other stables can put in a claim before the race. If several claim the same horse, one is picked at random and the others are refunded.
- You keep any purse your horse wins in that race.

## Breeding
- Retire a horse to breeding to stand a stallion or breed a mare. Retirement is permanent.
- Stallion owners set a stud fee and a book size. Your own mares breed to your own stallions for free.
- Coat colour follows real horse genetics. A genetic test shows a horse''s genes.

## Leaving the stable
- Pensioning sends a horse to the retirement home. It no longer races or breeds.
- The TRP (Thoroughbred Rehoming Program) takes a horse out of the game and returns a creation credit. It opens once the horse has been offered in a claiming race or at auction.

## Fair play
- One stable per person.
- Be kind on the forum and in chat. Admins have the final say.'),
('guide', 'How to play', '## 1. Open your stable
Log in on the forum, then come back to the game and name your stable.

## 2. Create your first horses
On your Dashboard, choose **Create a foundation horse**. Pick a name, sex, surface and age. Each one uses a creation credit. Older horses can race straight away; 2-year-olds have time to grow.

## 3. Read the trainer''s notes
Open a horse to see its trainer''s notes. They tell you what the horse is good at, and which distances and running style suit it. Hire a better trainer for a more reliable report.

## 4. Train
Press **Train** once a day on each horse that hasn''t reached its peak. Training brings young horses on faster.

## 5. Enter a race
Go to [Races](?page=races), pick a day and a race, and use **Enter a horse** at the bottom of the race page. Only horses that fit the race are listed. Watch your horse''s Energy: rest it between races.

## 6. Check the results
After race time, open the race to see the chart: where each horse was at every call, the margins and the times. Purses go straight into your Cash.

## 7. Breed
When a horse''s racing days are done, **Retire to breeding** on its page. Book your mares to stallions on the [Stallions](?page=studs) page.

Questions? Ask on the [forum](/Forum).');
