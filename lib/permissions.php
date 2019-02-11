<?php
function PermissionsToString( $permissions )
{
	$permissionsStr = [ "Spam", "Banned", "Unregistered", "Registered", "Developer", "Moderator", "Admin", "Root" ];
	return $permissionsStr[$permissions - ( \RA\Permissions::Spam )]; //	Offset of 0
}
