export type BootData = {
  user: {
    name: string;
    role: string;
    roleLabel: string;
    initials: string;
  };
  csrfToken: string;
  basePath: string;
  routes: {
    logout: string;
    prospectCreate: string;
    chatReviewCreate: string;
  };
  abilities: {
    accessPerformance: boolean;
    accessChatReviews: boolean;
    accessKnowledgeQueue: boolean;
    manageUsers: boolean;
    manageSystemSettings: boolean;
  };
};

declare global {
  interface Window {
    __SGB_BOOT__: BootData;
  }
}

export const boot = window.__SGB_BOOT__;

export async function fetchJson<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export async function sendJson<T>(url: string, payload: unknown, method = "POST"): Promise<T> {
  const response = await fetch(url, {
    method,
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": boot.csrfToken,
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(body || `Request failed: ${response.status}`);
  }

  return response.json() as Promise<T>;
}
