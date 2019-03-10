<?php

namespace RA;

abstract class Permissions
{
	const Spam = -2;
	const Banned = -1;
	const Unregistered = 0;
	const Verified = 1;
	const JrDeveloper = 2;
	const Developer = 3;
	const Moderator = 4;
	const Admin = 5;
	const Root = 6;
}
