#!/usr/bin/env python
#-*- coding: utf-8 -*-
#Copyright (C) 2011 ISVTEC SARL
#$Id$
__author__ = "Ousmane Wilane ♟ <ousmane@wilane.org>"
__date__   = "Fri Nov 11 16:54:17 2011"

from django.test import TestCase
from django.core.urlresolvers import reverse
from enterprise.models import Clients, Invitation
from django.utils.translation import ugettext_lazy as _
from django.core import mail
from django.conf import settings
import logging
logger = logging.getLogger('wf')
settings.DEBUG=True
settings.AUTHENTICATION_BACKENDS = (
     'libs.auth.WFMockRemoteUserBackend',
)


class AddCompanyTest(TestCase):
    def setUp(self):
        # We need a ticket and an account
        self.username = 'ousmane@wilane.org'
        self.ticket = ''
         

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
                                    {'name': 'foo baz',
                                     'email':'test@example'},
                                    follow = True)
        self.assertFormError(response, 'form', 'email', [_("Enter a valid e-mail address.")])
        response = self.client.post(url,
                                    {'email':'test@example.org'},
                                    follow = True)
        self.assertFormError(response, 'form', 'name', [_("This field is required.")])
        
        response = self.client.post(url,
                                    {'name': 'foo baz',
                                     'addr1': 'no where',
                                     'zip': 142,
                                     'city': 'Dakar',
                                     'country':u'SN'},
                                    follow = True)
        self.assertEqual(Clients.objects.count(), count + 1)
        self.assertContains(response, _("My companies"))


        # Test return_url
        response = self.client.post("%s?return_url=http://example.org" %url,
                                    {'name': 'foo baz',
                                     'addr1': 'no where',
                                     'zip': 142,
                                     'city': 'Dakar',
                                     'country':u'SN'},
                                    follow = True)

        self.assertEqual(response.status_code, 200)
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
        
        
    def test_invite_user(self):
        url = reverse("invite_user")
        response = self.client.get(url)

        self.assertEqual(response.status_code, 302)

        self.client.login(username=self.username, ticket=self.ticket)
        response = self.client.get(url)
        self.assertEqual(response.status_code, 200)

        self.assertTemplateUsed(response, 'enterprise/invite_user.html')

        count = Invitation.objects.count()
        
        response = self.client.post(url,
                                    {'first_name': 'Foo',
                                     'last_name': 'Baz',
                                     'company':1,
                                     'email':'test@example.org'},
                                    follow = True)


        self.assertEqual(Invitation.objects.count(), count + 1)
        self.assertContains(response, _("My companies"))
        self.assertEqual(len(mail.outbox), 1)
        subject = _("Invitation to join ISVTEC from Ousmane Wilane")
        self.assertEqual(mail.outbox[0].subject, subject)
        self.client.logout()

    def test_accept_invitation(self):
        #FIXME: Need mock
        pass
