(() => {
    'use strict';

    const getConfig = () => {
        const config = window.modpressManager || {};
        const factory = config.requestFactory || {};
        const restUrl = config.restUrl || factory.base_url || factory.url || config.apiRoot || config.requestUrl || (typeof window.wpApiSettings !== 'undefined' ? window.wpApiSettings.root : '/wp-json/');
        const nonce = config.restNonce || config.nonce || factory.nonce || '';

        return {
            restUrl: String(restUrl || '/wp-json/').replace(/\/+$/, '') + '/',
            restNonce: nonce,
            nonce,
            ...config,
        };
    };

    const buildRequestUrl = (baseUrl = '', route = '') => {
        const normalizedBase = String(baseUrl || '/wp-json/').replace(/\/+$/, '') + '/';
        const normalizedRoute = String(route || '').replace(/^\/+/, '');

        return normalizedRoute ? normalizedBase + normalizedRoute : normalizedBase;
    };

    const fetchJson = async (url, method, headers, body) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            body,
        });

        const responseText = await response.text();
        if (!response.ok) {
            let message = 'The request could not be completed.';

            try {
                const data = responseText ? JSON.parse(responseText) : {};
                if (data && data.message) {
                    message = data.message;
                }
            } catch (error) {
                // Ignore malformed error payloads and fall back to the default message.
            }

            throw new Error(message);
        }

        if (!responseText) {
            return {};
        }

        try {
            return JSON.parse(responseText);
        } catch (error) {
            return {};
        }
    };

    const request = async (payload = {}, options = {}) => {
        const config = getConfig();
        const requestOptions = { ...config, ...options, ...payload };
        const route = String(requestOptions.route || requestOptions.endpoint || '').trim();

        if (!route) {
            throw new Error('ModPress REST requests require a route value.');
        }

        const method = String(requestOptions.method || 'GET').toUpperCase();
        const nonce = requestOptions.nonce || requestOptions.restNonce || config.restNonce || config.nonce || '';
        const params = {};

        Object.entries(requestOptions).forEach(([key, value]) => {
            if (['route', 'endpoint', 'method', 'nonce', 'restNonce', 'restUrl', 'apiRoot', 'requestUrl'].includes(key)) {
                return;
            }

            if (value === undefined || value === null) {
                return;
            }

            params[key] = value;
        });

        const url = buildRequestUrl(config.restUrl, route);
        const headers = {
            'X-WP-Nonce': nonce,
        };

        if (method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            return fetchJson(url, method, headers, JSON.stringify(params));
        }

        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            const nextValue = typeof value === 'object' ? JSON.stringify(value) : String(value);
            query.append(key, nextValue);
        });

        const queryString = query.toString();
        if (queryString) {
            return fetchJson(`${url}${url.includes('?') ? '&' : '?'}${queryString}`, method, headers, undefined);
        }

        return fetchJson(url, method, headers, undefined);
    };

    window.ModPressAjax = Object.assign(window.ModPressAjax || {}, {
        getConfig,
        request,
        dispatch: request,
    });
})();
