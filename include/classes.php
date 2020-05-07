<?php

class mf_github_updater
{
	function __construct(){}

	function pre_set_site_transient_update_plugins($transient)
	{
		if(property_exists($transient, 'checked')) // && $checked = $transient->checked
		{
			$option_github_updater_next_try = get_site_option('option_github_updater_next_try');

			if(date("Y-m-d H:i:s") > $option_github_updater_next_try)
			{
				$option_github_updater_last_success = get_site_option_or_default('option_github_updater_last_success', date("Y-m-d H:i:s", strtotime("-1 year")));

				$owner_repos = "frostkom";
				$url_repos = "https://api.github.com/users/".$owner_repos."/repos";

				list($content, $headers) = get_url_content(array(
					'url' => $url_repos,
					'catch_head' => true,
					'headers' => array(
						'If-Modified-Since: '.gmdate("D, d M Y H:i:s", strtotime($option_github_updater_last_success))." GMT",
					),
				));

				$ratelimit_limit = (isset($headers['X-Ratelimit-Limit']) ? $headers['X-Ratelimit-Limit'] : "");
				$ratelimit_remaining = (isset($headers['X-Ratelimit-Remaining']) ? $headers['X-Ratelimit-Remaining'] : "");
				$ratelimit_reset = date("Y-m-d H:i:s", (isset($headers['X-Ratelimit-Reset']) ? $headers['X-Ratelimit-Reset'] : strtotime("+1 hour")));

				//do_log("GitHub RateLimit: ".$ratelimit_remaining."/".$ratelimit_limit);

				/*$content = '[
				  {
					"id": [number],
					"node_id": "[id]",
					"name": "[name]",
					"full_name": "[user]/[repo]",
					"private": false,
					"owner": {
					  "login": "[user]",
					  "id": [number],
					  "node_id": "[id]",
					  "avatar_url": "[url]",
					  "gravatar_id": "",
					  "url": "[url]",
					  "html_url": "[url]",
						...
					  "received_events_url": "[url]",
					  "type": "User",
					  "site_admin": false
					},
					"html_url": "[url]",
					"description": "[text]",
					"fork": false,
					"url": "[url]",
					...
					"deployments_url": "[url]",
					"created_at": "2016-12-20T08:43:16Z",
					"updated_at": "2020-02-07T08:35:32Z",
					"pushed_at": "2020-02-07T08:35:30Z",
					"git_url": "[url]",
					...
					"svn_url": "[url]",
					"homepage": "",
					"size": 167,
					"stargazers_count": 0,
					"watchers_count": 0,
					"language": "PHP",
					"has_issues": true,
					"has_projects": true,
					"has_downloads": true,
					"has_wiki": true,
					"has_pages": false,
					"forks_count": 0,
					"mirror_url": null,
					"archived": false,
					"disabled": false,
					"open_issues_count": 0,
					"license": null,
					"forks": 0,
					"open_issues": 0,
					"watchers": 0,
					"default_branch": "master"
				  }
				]';
				$headers['http_code'] = 200;*/

				switch($headers['http_code'])
				{
					case 200:
					case 201:
						$json = json_decode($content, true);

						if(is_array($json) && count($json) > 0)
						{
							foreach($json as $repo)
							{
								$repo_name = $repo['name'];
								$repo_updated = date("Y-m-d H:i:s", strtotime($repo['updated_at']));

								$plugin_file = WP_PLUGIN_DIR."/".$repo_name."/index.php";
								$theme_file = get_theme_root()."/".$repo_name."/style.css";

								if(file_exists($plugin_file))
								{
									$file_dir = $plugin_file;
								}

								else if(file_exists($theme_file))
								{
									$file_dir = $theme_file;
								}

								else
								{
									$file_dir = "";
								}

								if($file_dir != '')
								{
									$file_datetime = date("Y-m-d H:i:s", filemtime($file_dir));

									$log_message = "GitHub: There is a new version of ".$repo_name." to be downloaded";

									if($file_datetime < $repo_updated)
									{
										//$file_content = get_file_content(array('file' => $file_dir));
										//$file_version = trim(get_match("/Version\: (.*?)\\n/is", $file_content, false));

										$file_date = date("Y-m-d", strtotime($file_datetime));
										$file_time = date("H:i", strtotime($file_datetime));
										$repo_date = date("Y-m-d", strtotime($repo_updated));
										$repo_time = date("H:i", strtotime($repo_updated));

										do_log($log_message." (".($file_date < $repo_date ? $file_date." < ".$repo_date : $file_time." < ".$repo_time." (".$file_date.")").")"); // format_date($file_datetime)." < ".format_date($repo_updated) // (".$file_version.")
									}

									else
									{
										do_log($log_message, 'trash');
									}
								}
							}

							update_site_option('option_github_updater_last_success', date("Y-m-d H:i:s"));

							do_log("GitHub Error: ", 'trash');
						}

						else
						{
							do_log("GitHub Error: I did not get any JSON for ".$owner_repos." (".$content.")");
						}
					break;

					case 304:
						// Do absolutely nothing. This means that nothing on GitHub has changed since last success
					break;

					case 403:
						update_site_option('option_github_updater_next_try', $ratelimit_reset);

						do_log("GitHub Error: I got a rate limit for ".$owner_repos." and did set next try to ".format_date($ratelimit_reset)." (".$ratelimit_remaining."/".$ratelimit_limit.")"); //." (".var_export($headers, true).", ".$content.")"
					break;

					default:
						do_log("GitHub Error: I could not connect to get the repos for ".$owner_repos." (".var_export($headers, true).", ".$content.")");
					break;
				}
			}

			else
			{
				do_log("GitHub Error: Wait until ".format_date($option_github_updater_next_try));
			}
		}

		return $transient;
	}
}