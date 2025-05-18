<?php

namespace santilin\churros\json;
use JsonPath\JsonObject;

interface JsonModelable
{
	public function createRootJson();
	public function getJsonObject(string $path, ?string $id): ?JsonObject;
	public function getJsonArray(string $path, ?string $id): ?array;
	public function getJsonValue(string $path);
}


