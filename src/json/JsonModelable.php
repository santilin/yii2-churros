<?php

namespace santilin\churros\json;
use JsonPath\JsonObject;

interface JsonModelable
{
	public function createJsonRoot();
	public function getJsonObject(string $path, ?string $id): ?JsonObject;
}


