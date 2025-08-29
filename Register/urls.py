from django.urls import path
from . import views

urlpatterns = [
    path('', views.Register_view, name='Register_home'),
]
