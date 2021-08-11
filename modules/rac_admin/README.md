# RAC Admin Module

This module controls the administrative tools of RAC. Aside from the user-facing interfaces of proposals and decisions, basically everything used by staff and/or the committee should live in this module.

Note that removing this module and starting from scratch would eliminate the display of ratings on decision pages, although the decisions themselves would remain.

Primarily, you should only need to do predictable updates here for a handful of things.

## Changing ratings

Occasionally the format/logic of ratings may need to change. You shouldn't update the currently-used rating class for this, because that would change existing ratings using that class. Instead, you should create a new rating class in the Ratings folder, following the naming convention `Rating[year][semester]` using the year and semester this rating class will first take effect.

Then you must update `RACRatingHelper::rating` to include logic concerning when this rating class should be used. The convention has been to have explicit rules for all dates that are in the past relative to the most current class, and return the most current class by default.

A better way might be to use an array of classes, and implement a method in the rating classes for returning whether they would like to handle the given proposal, but there are only so many hours in the day.

Also, I have avoided having ratings inherit from anything but AbstractRating. It leads to some duplicated code, but I'm trying to avoid a hellish train of dependencies where you fix a bug in a rating class from 5 years ago, and now everything is broken.
