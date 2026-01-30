=== Database Cleaner ===
Contributors: TigrouMeow
Tags: database, clean, cleaner, optimize, sql
Donate link: https://www.patreon.com/meowapps
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

User-friendly tool to clean and optimize databases. Efficiently manages large databases, simplifying repair and ensuring peak performance.

== Description ==

Database Cleaner is packed with features to handle big databases, built from years of experience with similar plugins. It works great with the latest WP and PHP versions and has an easy-to-use interface. For large tasks, it uses asynchronous requests to avoid errors and timeouts, ensuring smooth processing. Check it out at [Database Cleaner](https://meowapps.com/database-cleaner/).

**IMPORTANT**: Always back up your data before using this or any plugin! While Database Cleaner is reliable, it's always best to be safe. Better safe than sorry! :)

== Features ==

- Easy Mode & Expert Mode: Whether you're a beginner or a pro, there's a mode for you. Easy mode simplifies everything, while Expert mode gives you full control.
- One-Click Cleaning: Save time with automatic cleaning. Just one click, and your database is tidy and optimized.
- Smart Data Management: Understand how plugins or themes use your data. The Pro Version offers even deeper insights.
- Detailed Statistics: Keep an eye on your database size with comprehensive stats and history tracking.
- Effortless Data Browsing: Explore and categorize your data with ease. Find what you need without any hassle.
- Big Database Handling: Perfect for large databases, it uses smooth, error-free asynchronous requests to manage heavy tasks.
- In-Depth Analysis: Specialized tools to analyze and monitor your data across Post Types, Tables, Options, and Cron Jobs.

== Why another Database Cleaner? ==

While other database cleaners are available, my experience with them revealed areas for improvement, like outdated UIs, incomplete features, and limited data analysis capabilities. Most notably, they struggled with large databases. Motivated by these challenges, I developed my own solution. Now, I'm dedicated to refining it to suit all types of WordPress and databases, aiming to make it the best for everyone.

I'm open to feedback and would be thrilled to discuss how Database Cleaner can better meet your needs. Let's chat and enhance this tool together!

== Installation ==

1. Upload the plugin to WordPress.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Meow Apps -> Database Cleaner in the sidebar and check the appropriate options.
4. Click on the button to clean your database automatically.

== Screenshots ==

1. No screenshot yet.

== Changelog ==

= 1.3.3 (2026/01/05) =
* Add: New option in the settings to control whether protected content can be deleted.

= 1.3.2 (2025/12/16) =
* Add: Expanded compatibility with additional plugins.

= 1.3.1 (2025/12/03) =
* Add: Expanded compatibility with additional plugins.
* 🎵 Discuss with others about Database Cleaner on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/database-cleaner/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 1.3.0 (2025/11/14) =
* Update: Enhanced UI and UX.
* Add: 'Protected Items' option to safely delete more items.
* Fix: Hotfix to ensure proper cleanup of scheduled hooks by refactoring cron entry removal.

= 1.2.9 (2025/11/12) =
* Add: Support for additional plugins.  
* Fix: Minor UI issues.

= 1.2.8 (2025/09/29) =
* Update: Enhanced support for more plugins.
* Update: Additional cron jobs to automate tasks.

= 1.2.7 (2025/08/27) =
* Add: Support for more plugins.
* Add: Assign Themes.
* Update: Better UI.

= 1.2.6 (2025/08/16) =
* Add: Ability to delete Custom Queries even if they are not completed.
* Add: Support for additional plugins.
* Add: Sweeper task reset functionality to manage long-running processes.
* Fix: Improved the UI and fixed minor bugs.

= 1.2.5 (2025/07/23) =
* Update: Added support for more plugins.
* Update: Refreshed common components.
* Fix: Corrected typo in users count retrieval process.
* Add: Introduced auto-refresh option for core count to ensure real-time updates.

= 1.2.4 (2025/07/02) =
* Fix: Better columns in the UI.
* Add: Uninstall script for easier plugin removal.
* Add: Support for additional plugins.

= 1.2.3 (2025/06/29) =
* Add: Optional Core Count for large databases.
* Update: Metadata retrieval functions now support filtering by specific post IDs.
* Add: Support for more plugins.

= 1.2.2 (2025/05/04) =
* Update: REST API responses now show autoload option values as 'yes' and 'no' instead of 'on' and 'off'.

= 1.2.1 (2025/05/01) =
* Add: Introduced plugin support to extend cleaning capabilities.
* Fix: Resolved issues in the Cron Jobs tab for proper display and functionality.
* Add: Added support for more options to increase customization.
* Update: Switched to raw NekoTables in the core for improved data visibility.
* Add: Expanded compatibility with additional plugins.

= 1.2.0 (2025/03/12) =
* Add: Implemented Cron Jobs filtering by schedule for better task management.
* Add: Introduced support for plugins in the table view.
* Fix: Resolved a crash issue occurring on page reload and under busy conditions.
* Fix: Added array validation for sizes in the ChartSizes component to prevent errors.

= 1.1.9 (2025/01/04) =
* Update: Better support for more plugins.
* 💕 Don't hesitate to share some love with us. If you enjoy Database Cleaner, please leave a review [here](https://wordpress.org/support/plugin/database-cleaner/). Also, any issues or feature requests you have, share it with us, we'll do our best.

= 1.1.8 (2024/12/22) =
* Update: Better support for more plugins.
* Fix: Translations with arrays issue.

= 1.1.7 (2024/11/27) =
* Fix: Unused Terms Columns.
* Fix: Duplicated Term Meta Select.
* Fix: Avoid some issues which were breaking the UI.

= 1.1.6 (2024/11/11) =
* Update: Better support for more plugins.

= 1.1.5 (2024/11/04) =
* Update: Minor enhancements.

= 1.1.4 (2024/09/19) =
* Update: Better support for more plugins.

= 1.1.3 (2024/08/25) =
* Update: Slightly cleaner UI.
* Update: Better support for more plugins.
* Update: Better logging.

= 1.1.2 (2024/07/29) =
* Fix: Threshold for posts revisions.
* Fix: A few warnings and notices.
* Update: Better support for more plugins.

= 1.1.1 (2024/07/08) =
* Fix: Post revisions.

= 1.0.9 (2024/06/28) =
* Fix: Fixed queries-related mutations.

= 1.0.8 (2024/06/21) =
* Update: Possibility to override the Used By.
* Update: Lot of UI enhancements and little code rewritings.

= 1.0.6 (2024/05/24) =
* Fix: Clear logs.
* Fix: Error handling in the UI.
* Update: Improved requests speed.
* Add: Support for more plugins.
* Security: Patched directory traversal vulnerability in log path.

= 1.0.5 (2024/04/07) =
* Update: Better plugins support.

= 1.0.3 (2024/01/20) =
* Fix: Deleting tables in bulk.
* Update: Better plugins support.
* Update: UI Enhancements.

= 1.0.2 (2023/12/20) =
* Update: Enhanced UI and how the items are ordered.
* Update: Better plugins support.

= 0.9.9 (2023/10/09) =
* Update: For better confidentiality, the logs file is now randomly generated.

= 0.9.8 (2023/10/03) =
* Update: Better UI for the Custom Queries.
* Add: Support more plugins.

= 0.9.7 (2023/09/19) =
* Add: More schedules for the Nyao Sweeper.
* Add: Build/Remove Indexes button in the Settings to optimizing the queries.
* Fix: Issues with Custom Queries.
* Add: More settings to craft yourself a better and nicer UI for Database Cleaner.

= 0.9.6 (2023/09/11) =
* Add: Release of a new feature! The Nyao Sweeper. It will constantly and discreetly clean your database in background, in tiny tasks to avoid impacting your server.
* Change: Reverse the size of the Logs, now from most recent to oldest.
* Add: DevTools, to help you debug and understand what is going on.
* Update: Support for more plugins.

= 0.9.5 (2023/08/18) =
* Update: Since the way Duplicated Post Meta is handled is tricky to understand, it shows the potential results first, but after the cleaning/analysis, it silently ignores then when they are actually not duplicates (that way, the results are more accurate).
* Fix: Native associations should not be overridable (since it has no effect anyway).

= 0.9.4 (2023/07/11) =
* Fix: Deleting metadata entries was not working with custom prefixes.

= 0.9.3 (2023/06/14) =
* Fix: Metadata tables were always using the default prefix.
* Update: Support for more plugins.

= 0.9.1 (2023/06/02) =
* Update: Metadata is loaded/filtered on the server-side (since it's usually too large).
* Update: Support for more plugins.
* Update: Better UI.

= 0.8.9 (2023/05/16) =
* Fix: Tables without the prefix were not handled correctly.
* Update: Support for more plugins.

= 0.8.8 (2023/05/06) =
* Update: Cleaned the options.
* Update: Support for more plugins.

= 0.8.7 (2023/05/05) =
* Update: Better UI.
* Update: More Age Thresholds.

= 0.8.6 (2023/05/02) =
* Add: Metadata tab, and filters for columns.
* Add: Support for AI Engine.
* ADd: Size filters (this can be improved in many ways however).
* Update: Fresh packages and bundles.

= 0.8.5 (2023/04/09) =
* Update: Better support for plugins.
* Add: Support for Freemius.

= 0.8.4 (2023/03/28) =
* Update: Lighter package.
* Update: New UI framework.
* Update: Better plugins support.
* Update: Support for PHP 8.2+.

= 0.8.3 (2023/03/13) =
* Update: Fully translatable.
* Update: Handle more use cases.

= 0.8.2 (2023/03/01) =
* Update: Optimized bundle size.
* Update: Handle more use cases.

= 0.8.0 (2023/02/16) =
* Update: Handle more use cases.
* Update: Refactored and cleaned some code. The plugin is actually now in a beautiful state!

= 0.7.9 (2023/02/01) =
* Update: Handle more use cases.
* Update: Hide the counters when an action is being done.

= 0.7.7 (2023/01/27) =
* Update: Handle more use cases.
* Update: Enhanced the entries modal, but still some work to do.

= 0.7.6 (2023/01/09) =
* Add: Checkboxes to avoid seeing everything in the entries modal (will make this better later).
* Update: Handle more use cases.

= 0.7.5 (2023/01/09) =
* Add: Repair tables.
* Update: Handle more use cases.
* Info: Happy 2023! 🎁

= 0.7.4 (2022/12/24) =
* Update: Handle more use cases.
* Update: Better handling for Duplicated Post Meta.

= 0.7.3 (2022/12/12) =
* Add: Ability to browse the data in a table. All the columns are displayed, we'll add a way to hide/show the columns independently later.
* Add: Better titles for modals.
* Add: Additional support for plugins.

= 0.7.2 (2022/11/23) =
* Add: Support for a lot of new plugins.
* Update: Cleaner UI, let me know how to improve it further.

= 0.7.1 (2022/11/15) =
* Add: New option to add a delay between each request. This is useful when you have a lot of data to clean, and you want to avoid timeouts.
* Update: The chart now works a bit differently and can switch between DB size and percent change.
* Fix: Little fixes for better requests.
* Add: New button to generate random fake data. Only for debugging purposes.

= 0.6.9 (2022/11/09) =
* Update: Better UI with better buttons, and colored alternative rows.
* Update: Used By data is now more overridable.

= 0.6.8 (2022/11/01) =
* Added: Support for WordPress 6.1.
* Added: Support for PHP 8.1.
* Added: Custom Queries.

= 0.6.7 (2022/10/19) =
* Fix: There were a few notices and warnings.
* Added: Support for more plugins.

= 0.6.6 (2022/10/15) =
* Update: We can now override the 'Used By'.
* Update: Optimize the 'Used By' feature.
* Add: Pingbacks.

= 0.6.5 =
* Add: Improved browsing of the data.
* Update: Improved the loading of data.

= 0.6.4 =
* Update: Chart use log scale.
* Update: Options are handled differently (UI will be more responsive).
* Add: Better support for plugins.

= 0.6.3 (2022/09/20) =
* Add: A little chart to show the evolution of the DB size.
* Add: Added support for Expired Transients.
* Fix: There was a bug while deleting certain cron jobs.

= 0.6.1 (2022/09/08) =
* Add: Support for more plugins.
* Update: Store everything in only one option instead of many.
* Update: Optimized the way options are loaded and updated.

= 0.6.0 (2022/09/06) =
* Update: Update the size of the DB every day automatically.
* Update: The UI is now a bit more elegant.
* Add: We can now disable the message which is above the dashboard.

= 0.5.9 (2022/08/29) =
* Fix: The columns weren't aligned properly.
* Fix: Tiny technical enhancements to make things smoother.

= 0.5.8 (2022/08/16) =
* Add: Easy/Expert Switch. The plugin now fits two kinds of user.
* Update: More UI improvements, we are getting close to perfection!? Let me know! 💕

= 0.5.7 (2022/08/10) =
* Fix: It was not possible to select many tables at once.
* Fix: Assigning a plugin to a specific cron task wasn't working.
* Add: New plugins support.
* Update: Little changes in the UI and how it works, should feel natural.

= 0.5.6 (2022/08/04) =
* Update: Assign Plugin now refreshes the items right away.
* Update: Assign Plugin now features a filter to associate to plugins faster.
* Update: Better UX when the plugin is busy.

= 0.5.5 (2022/07/23) =
* Fix: Could not delete or optimize tables without prefix.
* Update: Use a button instead of a link to associate an item with a plugin.

= 0.5.4 (2022/07/19) =
* Add: Now possible to select a range of checkboxes/items by using SHIFT.

= 0.5.3 (2022/07/12) =
* Fix: There was a little UI issue with the columns.
* Add: Deep-Cleaning option for Pro.
* Add: We can now see/check all the data of all Posts through paging (by using the glass magnifier icon).

= 0.5.1 (2022/06/24) =
* Update: My library Neko UI was improved (that will impact the UI positively).

= 0.4.9 (2022/06/03) =
* Update: Enhanced the loading of the data of the first tab, for a smoother and nicer experience.
* Update: Additional support for other plugins.

= 0.4.7 (2022/05/25) =
* Update: Lot of little UI enhancements.
* Update: Additional support for other plugins.

= 0.4.6 (2022/05/19) =
* Fix: The handling of the _user_roles option was wrong.
* Update: Icons have been updated; the trash means some data will be removed, while the cross means the item will be entirely removed (in a case of a table, it means it will be dropped).

= 0.4.5 (2022/05/16) =
* Update: Better handling of the Used By column with support of regexp.
* Update: Little UI enhancements to avoid extra clicks.
* Fix: Retrieve better option value for the modal.

= 0.4.4 (2022/05/10) =
* Add: We can now check what is the data stored by an option.

= 0.4.3 (2022/05/03) =
* Fix: Better UI for handling the Used By.

= 0.4.2 (2022/04/29) =
* Fix: Better support for ACF and various other plugins.
* Fix: Little UI enhancements to handle the "Used By" data.

= 0.4.1 (2022/04/24) =
* Add: Support for item used by frameworks.
* Fix: Better support for Elementor.
* Update: Lot of UI enhancements. More to come next week!

= 0.3.9 (2022/04/18) =
* Fix: Removed some warnings on the PHP side which were sometimes breaking the asynchronous requests.
* Add: We can now copy/paste the whole customized data related to Used By.

= 0.3.8 (2022/04/15) =
* Add: Sort by name, size, used by, etc.
* Add: Search for name, used by, etc.
* Fix: There was an issue with the count for transients.
* Update: Better filters, improved UI.

= 0.3.7 (2022/04/13) =
* Add: User can now choose a plugin for the "Used By" column.
* Fix: Removed a few warnings and enhanced the filters.

= 0.3.6 (2022/04/10) =
* Fix: The count was wrong for Post Types.

= 0.3.4 (2022/04/08) =
* Fix: Better handling of "Used By" overrides (to make sure we get some better information about how an item is used).
* Add: Checkbox to hide the items "Used by WordPress".
* Add: Paging for Post Types, Tables.
* Fix: Statistics were not updated after Auto Clean.
* Update: Again, many UI enhancements.

= 0.3.0 (2022/04/05) =
* Update: Many UI enhancements, buttons were simplified and actions moved on the left (to make it clearer which item it is linked to), smoother busy statuses.
* Add: Autoload is now a checkbox (which we can enabled/disabled).

= 0.2.9 (2022/03/30) =
* Update: Changed the way (and filters) the items can be selected in bulk.

= 0.2.8 (2022/03/29) =
* Fix: There was some issues with deleting cron jobs.
* Fix: Little UI issues.
* Update: Possibility to select more than one item at the time.

= 0.2.7 (2022/03/22) =
* Add: Cron Tabs.

= 0.2.6 (2022/03/18) =
* Fix: The percentage was sometimes off.
* Add: Support for Meow Apps plugins.

= 0.2.5 (2022/03/15) =
* Add: Ability to see how much the DB increased or decreased over time.
* Add: Checkbox to select tables.
* Update: Improved UI.

= 0.2.4 (2022/03/11) =
* Fix: Compatibility with MariaDB.
* Add: Optimize for tables.
* Update: Better UI for Options.
* Update: UI enhancements.

= 0.2.1 (2022/03/08) =
* Add: Support for "Used By" for WordPress Core, WooCommerce, and the whole system behind it (actions, filters).
* Update: Better SQL queries.
* Update: Better UI.

= 0.1.5 (2022/03/04) =
* Add: A way to look into the data which is going to be removed.
* Add: More ways to delete in bulk.
* Fix: A few UI bugs related to refreshing.
* Update: The UI is always evolving! Better and better! (and we are not done)

= 0.1.2 (2022/02/21) =
* Update: Better buffered deletions.
* Update: Dynamicity of the UI has improved a lot.

= 0.1.1 (2022/02/15) =
* Add: Finally, support for big installs, with buffered deletions!
* Update: A bunch of fixes and enhancements.
= 0.1.0 (2022/01/26) =
* Update: Doesn't work with risk level anymore, but a simpler option.
* Update: UI improvements.

= 0.0.7 (2022/01/11) =
* Add: Logging.
* Update: More information about what the plugin is cleaning after clicking the Clean DB button.

= 0.0.6 (2021/12/20) =
* Update: Nice UI improvements.

= 0.0.5 (2021/12/14) =
* Add: Support for removing tables, and check by which plugins they are used.
* Add: Ignore status for Risk column.
* Info: Additional and various enhancements.

= 0.0.4 =
* Fix: The default Risk Treshold was too high.

= 0.0.3 =
* Update: Small improvements, tables percentages, etc.

= 0.0.2 =
* Add: Table statistics.
* Update: Improve UI (buttons, organization, etc).

= 0.0.1 =
* Info: First release.
