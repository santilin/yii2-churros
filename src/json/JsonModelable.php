<?php

namespace santilin\churros\json;
use JsonPath\JsonObject;

interface JsonModelable
{
	public function loadRootJson(int $flavor = 0): ?JsonObject;
	public function getJsonObject(string $path, ?string $id): ?JsonObject;
	public function getJsonArray(string $path): ?array;
	public function getJsonValue(string $path);
}


