#!/usr/bin/env python
#-*- coding: utf-8 -*-
#Copyright (C) 2011 ISVTEC SARL
#$Id$
__author__ = "Ousmane Wilane ♟ <ousmane@wilane.org>"
__date__   = "Fri Nov 11 16:54:17 2011"

from django.test import TestCase
from django.core.urlresolvers import reverse
from enterprise.models import Clients
from django.utils.translation import ugettext_lazy as _

class AddCompanyTest(TestCase):
    def setUp(self):
        # We need a ticket and an account
        self.username = 'ousmane@wilane.org'
        self.ticket = 'e941a823d7dc6c32e65b47f334e8c361598711581fb4e47e55b2ee85fba5431c186e22dca26d6184'
         

    def test_add_company(self):
        url = reverse("add_company")
        response = self.client.get(url)

        self.assertEqual(response.status_code, 302)

        self.client.login(username=self.username, ticket=self.ticket)
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)

        self.assertTemplateUsed(response, 'enterprise/add_company.html')

        count = Clients.objects.count()
        response = self.client.post(url,
                                    {'nom': 'foo baz',
                                     'email':'test@example'},
                                    follow = True)
        self.assertFormError(response, 'form', 'email', [_("Enter a valid e-mail address.")])
        response = self.client.post(url,
                                    {'id_company_type': 1,
                                     'nom': 'foo baz',
                                     'email':'test@example.org'},
                                    follow = True)

        self.assertEqual(Clients.objects.count(), count + 1)
        self.assertContains(response, _("My companies"))
        self.client.logout()
        

    def test_change_company(self):
        url = reverse("change_company", kwargs={'customer_id':1})
        response = self.client.get(url)

        self.assertEqual(response.status_code, 302)

        self.client.login(username=self.username, ticket=self.ticket)
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)

        self.assertTemplateUsed(response, 'enterprise/add_company.html')
        self.client.logout()
        
        
