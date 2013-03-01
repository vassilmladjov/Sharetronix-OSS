<?php
	class activityFactory
	{
		public static function select($type)
		{
			switch ($type)
			{
				case 'user':
					return new activityUser();
					break;
				case 'group':
					return new activityGroup();
					break;
				case 'dashboard':
					return new activityDashboard();
					break;
				case 'search':
					return new activitySearch();
					break;
				case 'view':
					return new activityView();
					break;
				case 'private':
					return new activityPrivate();
					break;
			}
		}
	}