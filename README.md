# Great Oak Meals Scheduling

Used by Great Oak Cohousing Association to schedule common meals over a several
month season. This comprises both a survey for workers to fill out, as well as
an auto-allocation algorithm looking for the best fit.

## Screenshots

![Survey](./screenshots/survey.png)
![Survey Results](./screenshots/survey_results.png)

[Structural Diagram in diagrams.net](https://app.diagrams.net/#Uhttps%3A%2F%2Fraw.githubusercontent.com%2Fwilln%2Fgo_meals_scheduling%2Fmaster%2Fdraw_io.xml)

## Terms:
* assignment - a collection, a bundle of meals shifts of the same job
  type assigned to a person as a block. This is used by the work system
  to assign labor.
  * For example: head cook - 2 meals per season
* instances - a term from the work system which refers to how many assignments
  of a job have been assigned to a worker.
* meal - an event where someone works to cook or clean for a single date
  * example: Wednesday, July 1st, 2016 at 6:15pm
* shift - an instance of a type of worker at a given meal
  * This can have some ambiguous meaning, so I try to be explicit with how I use it.
  * There were 416 shifts assigned for the summer 2017 season.
  * For example, each meal needs at least 6 shifts of labor between cooks and
    cleaners.

## Types of meal-days and number of shifts per job:
* weekday meals (Mondays, Tuesdays, and Wednesdays)
  * 1 head cook
  * 2 asst cooks
  * 3 cleaners
  * 1 table setter (sometimes)
* Sunday meals
  * 1 head cook
  * 2 asst cooks
  * 3 cleaners
  * 1 table setter (sometimes)
* Meeting night meals (Meeting nights are the 1st Wednesday and the 3rd Monday)
  * 1 orderer (head cook)
  * 1 cleaner

## Exceptions
We don't schedule meals on holidays, and sometimes the day before / after, consider these dates each season. Currently skipped dates:
* Jan 1st
* Easter Sunday
* 4th of July
* Memorial Day (Sunday & Monday)
* Labor Day (Sunday & Monday)
* Halloween
* Christmas Eve & Christmas
* Thanksgiving
* Sunday following Thanksgiving
* New Years Eve

## Outline of duties for each season
1. between work seasons
2. count number of assignments for upcoming season
3. setup the availability survey
4. remind participants who haven't submitted their survey yet
5. close the survey & do assignments
6. enter the schedule into Gather
7. send email publicizing that the schedule is ready
8. resolve any issues with schedule

## Details of Duties for each season:
1. between work seasons
  * work on fixing bugs
  * adding / polishing features
  * making changes based on work system changes
  * deal with hosting headaches
  * note: lately I haven't had much time to do this since we lowered the # of hours to 2/month (8 per season), and things have been "good enough"
2. count number of assignments for upcoming season
  * need to figure out how many of each meal type is required for the upcoming season to determine how many shifts we'll need for each type of work.
  * round up to nearest "full assignment"
    * the math doesn't always work out cleanly
    * don't want to overly burden work committee with half shifts
    * a touch of slack tends to be very helpful for scheduling
    * the fractional hours lost don't matter much, as the work assignments are never completely uniform
  * send report of numbers to the meals committee before the work survey begins
3. (after work survey) setup the scheduling availability survey
  * grab work assignments file from the work survey webserver, and put into proper place
  * update deadline for closing of survey
  * confirm the dates look correct in the survey
  * make any last-minute changes to staffing
    * swaps from current season into future
    * any volunteers for shifts work was unable to fill
  * get the new job IDs for the season
  * test that system is working
  * setup routine backups
  * notify participants that the survey has started
4. remind participants who haven't submitted their survey yet
   1.  a few times during the week or so
   2. it actually helps me to have a few non-responders, because they can be scheduled anywhere
5. close the survey & do assignments
   1. commit changes (so that we could come back to this state)
   2. run the auto-assignment process
   3. the algorithm essentially looks like this:
      1. Examine each of the job types
         1. example: weekday head cook
      2. For each job, get the list of dates where a shift would be assigned. Example for summer season meeting night orderer:
         1. 5/3/2017
         2. 5/15/2017
         3. 6/7/2017
         4. 6/19/2017
         5. 7/5/2017
         6. 7/17/2017
         7. 8/2/2017
         8. 8/21/2017
      3. Sort (from lowest to highest) this list of dates based on the number of people who can work that date, so that we're working on the hardest to fill date first.
         1. example: 7/17/2017
      4. Take the list of workers who are assigned to work that job type, and assign each one an arbitrary number of points. Various things factor into this point score:
         1. skip if worker is fully assigned for this job type
         2. skip if worker said "conflict" for this shift-date
         3. skip if worker has already been assigned this shift already
            1. remember there are multiple asst cooks and cleaner shifts per meal
         4. check "clean after cook" - if they've already been assigned the "other half" of this meal and they want to clean after cooking
            1. example: current job being examined is assistant cook, and they already have a cleaning shift for that date, do they have that preference checked?
         5. their availability score, this depends on how many shifts they've been assigned, and how "available" their schedule is.
            1. examples:
               1. Wanda should clean 4 meals, and is available to work 21 dates
               2. James should clean for 8 meals, and is available to work 9 dates
            2. James would get a higher score for this date
         6. If this is a group-clean job (not a meeting night cleaner or cook), and they're named as a preferred hobarter, and no other preferred hobarter is assigned already
         7. check to see if other people who are already assigned to work this half of meal have marked this worker with a
            1. prefer to work with
            2. avoid working with
         8. check to see if the current worker has a preference with anyone else who has already been assigned
            1. prefer to work with
            2. avoid working with
         9. do they already have "adjacent" dates assigned
            1. this is avoid assigning someone to work 2 or 3 days in a row, to space them out
      5. Add up the points for each worker, sort and assign the shift
      6. Display the proposed schedule
   4. fill in the "holes"
      1. my code isn't perfect, so there are often a number of shifts which haven't been filled. Usually because it gives up if there are too many constraints.
      2. generate a report of which workers weren't fully assigned
      3. try to fit them into the holes, which usually requires making some trades
         1. for a given hole, look at the survey preferences to see which people can work that day
         2. for a worker who isn't fully-assigned (John), either stick them into that slot, or look for someone who can work that day (Betty), find a different day when Betty is assigned, confirm that John can work that day, and do the trade.
            1. sometimes this can be a complicated 3-way (triangle trade)
      4. If the hole is not fillable (this happens sometimes with the meeting night orderers), then contact meals & work to explain the issue and ask for help
      5. read the requests & comments and make sure the schedule fulfills people's special requests as much as possible
         1. don't go down deep rabbit holes
         2. do trades when needed
         3. sometimes this involves doing a lot of trades by hand in order to fulfill a request for mega-cook, since I haven't written the functionality to do this yet.
      6. confirm that no table setters have been assigned to also work as a cook that day
         1. (this is a manual workaround for a lack of functionality)
      7. run an automated validation check to catch any other problems
      8. manually confirm once more that after the trades not many of the requests remain unfulfilled
6. enter the schedule into Gather
   1. since Tom & I haven't spent the time to support uploads yet, I've been adding this manually, doing the clickety-click for every shift
      1. this can be about 30 or so clicks plus some typing for entering names for each meal
      2. need to pay attention to make sure that mistakes aren't made (e.g. Kate vs Katie)
      3. alter the details for meeting night meals
         1. earlier time
         2. only GO, no SW or TS
      4. resolve any reservation conflicts - usually this is done by modifying their reservation and notifying that person
   2. I would love to have this be an automated process BUT:
      1. usernames between Dale's work survey and Gather are different
         1. we could use normalized usernames
         2. we could use single-sign-on
      2. need to write an import routine into Gather
7. send email publicizing that the schedule is ready
8. resolve any issues with schedule
   1. if I made a mistake, then arrange trades and send out emails asking for swaps
      1. Once published, people are likely to have made copies of their schedule
      2. Also, the respondent's availability survey results are likely a week or more old, so their plans may have changed
   2. if the mistake is not mine, then point out the survey report so that they can have an easier, more targeted time finding a swap for themselves

## Historical reference

### FALL 2017 common meals  
* 40 Weekday meals
* 15 Sunday meals
* 8 Meeting night meals 


### Meal shifts we request:
- Weekday head cook (20 instances 2 HRS/Month)
- Weekday meal asst cook (40 instances 1 HRS/Month)
- Weekday Meal Cleaner (30 instances 1.5 HRS/Month)
- Weekday table setter (10 instances 0.5 HRS/Month)
- Meeting night takeout orderer  (4 instances 0.5 HRS/Month)
- Meeting night cleaner  (4 instances 0.75 HRS/Month)
- Sunday head cook  (8 instances 2 HRS/Month)
- Sunday meal asst cook  (15 instances 1 HRS/Month)
- Sunday Meal Cleaner  (12 instances 1.5 HRS/Month)

### Formula for calculating the number of shifts:
- Let M = number of meals we're trying to cover (for Sundays, 12)
- Let W = number of workers of this type assigned to each meal (e.g. 3 cleaners)
- Let S = number of meals per assigned shift (2 for cooks, 4 for cleaners)
- The formula would then be: (M * W) / S


