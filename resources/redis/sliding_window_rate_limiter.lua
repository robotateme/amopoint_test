local key = KEYS[1]
local now_ms = tonumber(ARGV[1])
local window_ms = tonumber(ARGV[2])
local max_attempts = tonumber(ARGV[3])
local member = ARGV[4]
local min_score = now_ms - window_ms

redis.call('ZREMRANGEBYSCORE', key, 0, min_score)

local current = redis.call('ZCARD', key)

if current >= max_attempts then
    local oldest = redis.call('ZRANGE', key, 0, 0, 'WITHSCORES')
    local retry_after_ms = 0

    if oldest[2] ~= nil then
        retry_after_ms = math.max(0, window_ms - (now_ms - tonumber(oldest[2])))
    end

    redis.call('PEXPIRE', key, window_ms)

    return {0, 0, retry_after_ms}
end

redis.call('ZADD', key, now_ms, member)

local updated = redis.call('ZCARD', key)
local remaining = math.max(0, max_attempts - updated)

redis.call('PEXPIRE', key, window_ms)

return {1, remaining, 0}
