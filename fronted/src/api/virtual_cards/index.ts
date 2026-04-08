import request from '@/utils/request';

export async function queryCardListAPI(params?: { [key: string]: any }) {
  return request.get('/cards', {
    params,
  });
}

export async function queryCardTransactionListAPI(params?: { [key: string]: any }) {
  return request.get('/card-transactions', {
    params,
  });
}

export async function createCard(params?: { [key: string]: any }) {
  return request.post('/cards', {
    ...params,
  });
}

export async function freezeCard(params?: { [key: string]: any }) {
  return request.post('/cards/freeze', {
    ...params,
  });
}

export async function unfreezeCard(params?: { [key: string]: any }) {
  return request.post('/cards/unfreeze', {
    ...params,
  });
}

export async function syncAllCard(params?: { [key: string]: any }) {
  return request.post('/cards/sync-all', {
    params,
  });
}

export async function syncMultipleCard(params: Record<string, any>) {
  return request.post('/cards/sync', {
    ...params,
  });
}

export async function cancelCard(params?: { [key: string]: any }) {
  return request.post('/cards/cancel', {
    params,
  });
}

export async function setCardLimits(params?: { [key: string]: any }) {
  return request.post('/cards/total-limit', {
    ...params,
  });
}

export async function setCardSingleLimit(params?: { [key: string]: any }) {
  return request.post('/cards/set-single-trans-limit', {
    ...params,
  });
}

export async function setCardBalance(params?: { [key: string]: any }) {
  return request.post('/cards/set-balance', {
    ...params,
  });
}

export async function syncCardTransactions(params?: { [key: string]: any }) {
  return request.post('/card-transactions/sync', {
    ...params,
  });
}

// Cards与FbAdAccount关联管理相关的API
export async function attachCardsToFbAdAccount(params: {
  fb_ad_account_id: string;
  card_ids: string[];
  default_card_id?: string;
}) {
  return request.post('/cards/attach-to-fb-ad-account', {
    ...params,
  });
}

export async function detachCardsFromFbAdAccount(params: {
  fb_ad_account_id: string;
  card_ids: string[];
}) {
  return request.post('/cards/detach-from-fb-ad-account', {
    ...params,
  });
}

export async function setDefaultCardForFbAdAccount(params: {
  fb_ad_account_id: string;
  card_id: string;
}) {
  return request.post('/cards/set-default-for-fb-ad-account', {
    ...params,
  });
}

export async function getFbAdAccountCards(params: {
  fb_ad_account_id: string;
}) {
  return request.get('/cards/fb-ad-account-cards', {
    params,
  });
}

export async function getCardFbAdAccounts(params: {
  card_id: string;
}) {
  return request.get('/cards/card-fb-ad-accounts', {
    params,
  });
}

export async function searchCardsByNumber(params: {
  number: string;
}) {
  return request.get('/cards', {
    params: {
      number: params.number,
    },
  });
}
