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
        let data = {};

        try {
            data = responseText ? JSON.parse(responseText) : {};
        } catch (error) {
            if (!response.ok) {
                throw new Error('The request could not be completed.');
            }
            return {};
        }

        const isLegacyAjaxResponse = data && Object.prototype.hasOwnProperty.call(data, 'success');
        const success = isLegacyAjaxResponse ? Boolean(data.success) : response.ok;
        const payload = isLegacyAjaxResponse ? (data.data || data) : data;

        if (!response.ok || !success) {
            const message = data && data.message ? data.message : (data && data.data && data.data.message ? data.data.message : 'The request could not be completed.');
            throw new Error(message);
        }

        return payload || {};
    };

    const request = async (payload = {}, options = {}) => {
        const config = getConfig();
        const requestOptions = { ...config, ...options, ...payload };
        const route = String(requestOptions.route || requestOptions.endpoint || '').trim();

        if (!route) {
            throw new Error('ModPress REST requests require a route value.');
        }

        if (typeof requestOptions.action !== 'undefined') {
            throw new Error('Legacy admin-ajax actions are no longer supported. Use a REST route instead.');
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

    const flattenEntryPayload = (source = {}, target = {}) => {
        if (!source || typeof source !== 'object') {
            return target;
        }

        Object.entries(source).forEach(([key, value]) => {
            if (value && typeof value === 'object' && !Array.isArray(value) && ['term', 'custom_fields', 'data', 'meta'].includes(key)) {
                flattenEntryPayload(value, target);
                return;
            }

            if (value && typeof value === 'object' && !Array.isArray(value) && key === 'custom_fields') {
                Object.entries(value).forEach(([customKey, customValue]) => {
                    target[customKey] = customValue;
                });
                return;
            }

            target[key] = value;
        });

        return target;
    };

    const normalizeEntryPayload = (data = {}) => {
        const source = data && typeof data === 'object' && data.data && typeof data.data === 'object' ? data.data : (data && typeof data === 'object' ? data : {});
        const flattened = flattenEntryPayload(source, {});
        const term = source.term && typeof source.term === 'object' ? source.term : (source && Object.prototype.hasOwnProperty.call(source, 'term_id') ? source : {});
        const customFields = source.custom_fields && typeof source.custom_fields === 'object' ? source.custom_fields : {};

        return {
            ...flattened,
            ...(term && typeof term === 'object' ? term : {}),
            ...(customFields && typeof customFields === 'object' ? customFields : {}),
        };
    };

    window.ModPressAjax = Object.assign(window.ModPressAjax || {}, {
        getConfig,
        request,
        dispatch: request,
        normalizeEntryPayload,
    });
})();
