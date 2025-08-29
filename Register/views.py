from django.shortcuts import render

def Register_view(request):
    if request.method == "POST":
        username = request.POST.get("username")
        password = request.POST.get("password")
        email = request.POST.get("email")
        # এখানে ইউজার তৈরি করার লজিক বসাতে পারো
        context = {"message": "Registration successful!"}
        return render(request, "register/register.html", context)

    return render(request, "register/register.html")
