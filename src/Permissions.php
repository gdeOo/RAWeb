<?php

namespace RA;

abstract class Permissions
{
	const Spam = -2;
	const Banned = -1;
	const Unregistered = 0;
	const Verified = 1;
	const Developer = 2;
	const Moderator = 3;
	const Admin = 4;
	const Root = 5;
}
