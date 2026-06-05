const DEFAULT_API_URL = 'https://recurlog.isoftro.com/api/v1';

const ENV_API_URL = process.env.EXPO_PUBLIC_API_URL?.trim();
const ENV_ASSETS_URL = process.env.EXPO_PUBLIC_ASSETS_URL?.trim();

const FALLBACK_API_URL = DEFAULT_API_URL.replace(/\/$/, '');

export const API_URL = (ENV_API_URL ?? FALLBACK_API_URL).replace(/\/$/, '');
export const API_BASE_URL = API_URL.replace(/\/api\/v1$/, '');
export const API_PREFIX = API_URL.endsWith('/api/v1') ? '/api/v1' : '';
export const ASSETS_URL = (ENV_ASSETS_URL ?? `${API_BASE_URL}/assets`).replace(/\/$/, '');

export const TOKEN_KEY = 'recurlog_token';
export const REFRESH_TOKEN_KEY = 'recurlog_refresh_token';
export const USER_KEY = 'recurlog_user';
