# Tornado Change Log

## Version 0.3

### New Features
 * Improved timeframe feature including “Past x days” and custom options
 * New Natural Language interface for Worksheet creation
 * Worksheets can now have names up to 255 characters long
 * Histograms now sort by size descending by default
 * Better support for low screen resolutions
 * Added support for the management of curated datasets
 * Added Duplicate Worksheet functionality
 * Improved API validation on page and per_page parameters
 * Removed worksheet locking for now
 * Worksheet ordering is now preserved
 * Users now get assigned brand permissions when created via the API
 * Unusable dimensions are now filtered out when choosing them
 * Improved layout of Tornado charts for balance
 * When a Tornado doesn’t have any data for one side, use a Histogram
 * Improved appearance of the Dimension search
 * Workbooks are archived when the recording is no longer present
 * Added Sample support
 * Rejigged Worksheet tab interface with scrolling - it now stays on one line
 * Added more token methods to the API
 * Outliers will now be on by default
 * Domains are now clickable in tooltips
 * Improved client-side build process
 * Added phantomjs support for tests

### Bugfixes

 * DataSift-curated baseline error handling improvement
 * Improved display of the values in the left hand side of Tornado charts
 * Last update timestamp on Worksheets now refreshes as appropriate
 * When a worksheet name is edited, show the new name in the top breadcrumb
 * Projects being updated/created with a pre-existing name now raise a 409 in the API
 * Recordings now appear in the project list as expected
 * Fixed issue where the current name of the workbook was not in the Edit Workbook dialog
 * Explore timeseries fixed
 * Added Workbook templates
 * Worksheet filters now reset on error
 * Filenames in the Export Workbook .zip file now end in .csv
 * Improved error messages in the Identity API
 * Dimension thresholds must now be greater than 0
 * Baseline outliers now displaying on the correct side of the axis when the primary data is zero
 * Improved response in scrollbars to changing of viewport height

Since: 98466fc9844229779c26dca6832c2d529faa609f

----------

## Version 0.2

 * Added documentation for Tornado

Since: 1e533d76832b3a15dc40ea22ab0e46f942e2f5ff

----------

## Version 0.1

 * Initial import of Tornado code

Since: 034e0d5f8f12485e190a256c1a5354c52a2c5f9b

----------
