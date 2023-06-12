(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.moderationDashboardActivity = {
    attach: function (context, settings) {
      var $activity = $('.moderation-dashboard-activity');

      once('moderation-dashboard-activity',".moderation-dashboard-activity",context).forEach(function (value,i) {
        /* global Chart:object */
        if ($activity.length && settings.moderation_dashboard_activity && Chart) {
          var default_activity_chart_height = 500;
          var activity_chart_height = 16 *
            settings.moderation_dashboard_activity.labels.length;

          if (activity_chart_height < default_activity_chart_height) {
            activity_chart_height = default_activity_chart_height;
          }
          var $canvas =
            $(`<canvas width="500" height="${activity_chart_height}"></canvas>`);
          $activity.append($canvas);

          new Chart($canvas, {
            type: 'bar',
            data: settings.moderation_dashboard_activity,
            options: {
              indexAxis: 'y'
            }
          });
        }
      });
    }
  };

}(jQuery, Drupal, once));
