# Quarter Pole: code and database

Working files for Quarter Pole, the browser racehorse sim (PHP, MySQL, HTML, CSS).
The design decisions live in the design doc: https://claude.ai/artifact/1UuQMPLWu8F44jjn2mhyve

## Files

- `public/`: the web root (point the domain here). `index.php` handles every page; `style.css` is the look.
- `src/`: game code (database, settings, login, stables, horse creation, color genetics, trainer notes, breeding, and the race engine in `src/Race/`).
- `templates/`: the HTML for each page.
- `bin/`: command-line scripts (the daily cron and the game stallion maker).
- `sql/migrations/`: changes to run, in order, on a database created before them.
- `sql/schema.sql`: every database table. `sql/seed.sql`: starting data, including all tunable settings, the 8 tracks (4 active at launch), the 6 series and a jockey pool.
- `config.example.php`: copy to `config.local.php` (never committed) and fill in the database login.

## Adding the game stallions

After loading the database, run `php bin/create_game_stallions.php` once. It creates 6 stallions per surface, 8 to 10 years old, standing at stud with fees based on how good each one rolled (pass a number to change how many per surface).

## The daily cron

Run `php bin/daily.php` once a day in the evening (for example `0 21 * * *`). It runs that day's races, restores Energy by each horse's Recovery, moves the game clock forward 2 days, writes basic trainer's notes for horses that just turned 2 and adds race cards to keep a week open. Running it twice in one day does nothing the second time. `php bin/daily.php --schedule-only` just adds missing race cards, which is handy right after setting up.

## Running it locally

1. Create a MySQL or MariaDB database, then load `sql/schema.sql` followed by `sql/seed.sql`.
2. Copy `config.example.php` to `config.local.php` and fill in the database details. Leave `auth` as `'dev'`.
3. From this folder, run `php -S 127.0.0.1:8080 -t public` and open http://127.0.0.1:8080.
4. Log in with any member number (test mode), name your stable, and create foundation horses.

## What works so far

- Test login (no forum needed) and the SMF login adapter for later.
- Naming a stable, which grants starting Cash and creation credits (logged in the Cash ledger).
- Creating foundation horses with a credit: random stats, running style, peak age, Endurance by surface and color genetics, plus a basic trainer's report.
- The stable page (cards, 12 per page) and the horse page (record, parents, COI, trainer's notes for the owner).
- Training: a Train button on each card and horse page, once per horse per real day. Horses grow from 75% of peak at age 2 to full peak at their peak age; each session adds 0.25 points of training progress (setting `training_progress_per_session`); 100 points means full peak, so daily training speeds development up without replacing it. They hold peak for 2 game years, then decline. Training earns a flat 1 AMI per real day, and AMI turns into creation credits automatically every 50 points.
- Breeding: retire a horse to breeding (permanent), stand a retired stallion at stud (fee and book size up to 100), genetic tests (200 Cash) that reveal the genotype, a stallion list filtered by surface, and the Proposed breed page with stud fee, COI, dud chance and the lethal-white risk when both parents are tested. Foals blend the parents' stats by Potency share plus a random swing, with greatness, duds (more likely when inbred), surface inheritance and Mendelian color genes. Backdated foals can be bred into any earlier open year for the mare, and a mare has one foal per year. Stud fees go to the stallion's owner; your own stallion is free.
- Racing: race cards for the coming week (claiming Monday, maidens and allowances Tuesday and Thursday, steeplechase Friday at Boudicca Park), entering with an entry fee, scratching for a refund, and claiming. The race engine works out each horse's speed every 1/16 mile from its stats, maturity, Energy, running style, the track's turns and stretch, the going and (over fences) Scope. Result charts show positions and lengths at each point of call, final times and the leader's fractions. Ghost horses fill fields up to 5; races with no player horses are cancelled. Purses (60/20/11/6/3), race AMI and Energy use are settled when the race runs.
- Trainer's reports: free basic notes at age 2 (the cron writes them when a foal turns 2), then Experienced (500) and Expert (1,000) trainers on the horse page. Each level is written once and never redone.
- Leaving the stable: pension a horse to the retirement home, or send it to the TRP for a creation credit back once it has been offered (entered in a claiming race, or at auction once auctions exist). Both are permanent.
- Admin settings page (`?page=admin-settings`, forum admins only; in test login tick "admin"): every number in `qp_settings`, grouped, with checks so numbers stay numbers.

## How the tables fit together

| Table | What it holds |
|---|---|
| `qp_settings` | Every number marked "to tune" in the design doc. Change values here, not in code. |
| `qp_game_clock` | The current in-game date. The daily cron moves it 2 days forward. Game year 1 is stored as 2001. |
| `qp_stables` | One row per player, keyed on the SMF member id. Cash, credits, AMI, Premium end date, login streak. |
| `qp_transactions` | Every Cash movement (purses, fees, sales, interest), so balances can always be checked. |
| `qp_ami_log` | Every AMI award and conversion to credits. |
| `qp_subscriptions` | Premium payments (provider decided later). |
| `qp_badges` | Badges each stable has earned, with tier. |
| `qp_horses` | Every horse ever, including the dead, rehomed and ghosts, so pedigrees always work. Holds hidden peak stats, color genotype, status, stud settings and career totals. |
| `qp_trainer_reports` | Static trainer notes, one per level. An upgrade adds a level; nothing is redone. |
| `qp_breedings` | Every breeding: sire, dam, fee paid, foal, and whether it was backdated or born dead. One foal per mare per game year is enforced here. |
| `qp_tracks` | The 8 tracks with their personality (stretch, turns, jumps, rain, surface profile). |
| `qp_jockeys` | The jockey pool. All skill 50 for now. |
| `qp_series` | The five Triples and the Breeders' Stakes Series. |
| `qp_races` | Every race card entry: track, level, surface, distance, age and sex rules, purse, fee, real and game date, status (including Cancelled). |
| `qp_entries` | Each horse in a race (player or ghost), with post, finish, margin, time, earnings and claims. |
| `qp_claims` | Claims on horses in claiming races. The price is held when the claim goes in; after the race one claim wins (a shake if several) and the rest are refunded. |
| `qp_race_calls` / `qp_race_fractions` | Points of call and fractional times for the result chart. |
| `qp_auctions` / `qp_bids` | 72-hour auctions with reserve prices. Buy-it-now has a column but is off at launch. |

## Login (SMF)

The game shares SMF's login. SMF lives at `/Forum` and the game at the site root. In SMF's admin, turn **off** "Enable local storage of cookies" so the login cookie covers the whole site. The game reads the logged-in member through SMF's `SSI.php`.

## Notes

- Game tables use the `qp_` prefix so they can sit in the same database as SMF's `smf_` tables.
- Stats are stored as each horse's **peak** values; the game works out current values from age and training.
