# Directions for setting up & running the Great Oak Meals Scheduling Survey

## Setup for the upcoming season or sub-season

Update settings in order to calculate the needed shift counts. The Meals
committee needs to know how much meals labor will be needed.

### Ask meals committee questions:
* How many meals are they planning on serving this season?
  - Will they be reducing the load down from the "full schedule"?
* Do they want to update the list of hobarters?
  - Look for `function get_hobarters`
* Do they want to offer CSA farm meals this summer? Disable for fall / winter.
  - Update `function doing_csa_farm_meals()`
* Which meal formula should we use?
  - Update instances of `Meal::BILLING_FORMULA`
* Where will these meals be hosted?
  - Update `LOCATIONS_TO_RESERVE`
* Which jobs will we be using this season?
  - Disable any un-needed meals in `public/utils.php` in the
  `get_meal_type_by_date()` function.

### Ask Process committee
* Are there any meeting nights that will be rescheduled?
   - Update `function get_weekday_overrides`

### Full `SUB_SEASON_FACTOR`

Set the `SUB_SEASON_FACTOR` to 1.0 for a 6-month season in order to get
the count needed for the full season.

### check that there aren't any other uncommitted modifications
`git status`

### update public/season.php
* set `SEASON_NAME` - use the combo season name (e.g. `FALL_WINTER`) to get the
  full 6 months.
* make sure the right months are included in `get_current_season_months()`
* remove any previous season's data:
  - `get_num_shift_overrides()`
  - `get_skip_dates()`
  - `get_weekday_overrides()`
  - `get_meeting_night_overrides()`

### Ensure the number of meals per assignment is correct:

In `public/config.php`, confirm in `function get_num_meals_per_assignment`, and
sync up with what's in the job assigments.

### Get the counts

Push the code to the webserver:

`./push_all_to_production.sh`

Open the scheduling system in a web browser, and check the summary report. At
the bottom, it should have a report listing the amount of labor needed.

## Continue
Continue on to the next step of [START](./DIRECTIONS_START.md)
