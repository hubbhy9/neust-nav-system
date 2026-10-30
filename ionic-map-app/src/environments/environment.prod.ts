const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';

export const environment = {
  production: true,
  phpBaseUrl: `${baseUrl}/caps`,
  apiUrl: `${baseUrl}/caps/api/index.php`
};
