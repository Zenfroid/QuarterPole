-- For databases created before the welcome, rules and guide pages.
-- Text pages edited on the admin panel.
CREATE TABLE qp_pages (
  slug        VARCHAR(20)  NOT NULL PRIMARY KEY,
  title       VARCHAR(100) NOT NULL,
  body        TEXT         NOT NULL,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
