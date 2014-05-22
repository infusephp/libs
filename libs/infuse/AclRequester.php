<?php

namespace infuse;

interface AclRequester
{
	public function id();
	public function groups( $owner );
}